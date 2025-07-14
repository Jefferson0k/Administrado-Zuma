<template>
    <Dialog v-model:visible="dialogVisible" modal header="Informacion de la hipoteca" :style="{ width: '50vw' }"
        @hide="handleClose">
        <div v-if="loading" class="flex justify-center p-4">
            <i class="pi pi-spin pi-spinner" style="font-size: 2rem"></i>
        </div>
        <iframe v-else :src="localPdfUrl" width="100%" height="700px"></iframe>
    </Dialog>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import Dialog from 'primevue/dialog';
import axios from 'axios';
import jsPDF from 'jspdf';

const props = defineProps({
    prestamosId: {
        type: [String, Number],
        required: true
    },
    visible: {
        type: Boolean,
        required: true
    }
});

const emit = defineEmits(['update:visible', 'close']);
const dialogVisible = ref(props.visible);
const loading = ref(true);
const localPdfUrl = ref('');
const prestamoData = ref(null);
let referenceNumber = '';

watch(() => props.visible, (newValue) => {
    dialogVisible.value = newValue;
    if (newValue && props.prestamosId) {
        cargarDatosPrestamo();
    }
});

watch(() => dialogVisible.value, (newValue) => {
    emit('update:visible', newValue);
    if (!newValue) {
        emit('close');
        limpiarPDF();
    }
});

const handleClose = () => {
    emit('close');
    limpiarPDF();
};

const limpiarPDF = () => {
    if (localPdfUrl.value) {
        URL.revokeObjectURL(localPdfUrl.value);
        localPdfUrl.value = '';
    }
};

const cargarDatosPrestamo = async () => {
    try {
        loading.value = true;
        const response = await axios.get(`/property-loan-details/${props.prestamosId}`);
        prestamoData.value = response.data;
        referenceNumber = response.data?.id ?? '';
        await generatePDF();
    } catch (error) {
        console.error('Error al cargar datos del préstamo:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(async () => {
    if (props.prestamosId) {
        await cargarDatosPrestamo();
    }
});

watch(() => props.prestamosId, async (newId) => {
    if (newId) {
        await cargarDatosPrestamo();
    }
});

const loadImageAsBase64 = (url, timeout = 10000) => {
    return Promise.race([
        new Promise((resolve, reject) => {
            // Detectar si es SVG por la extensión o content-type
            const isSvg = url.toLowerCase().includes('.svg') || url.includes('svg');
            
            if (isSvg) {
                // Manejar SVG como antes
                fetch(url)
                    .then((res) => res.text())
                    .then((svgText) => {
                        const svgBlob = new Blob([svgText], { type: "image/svg+xml" });
                        const urlBlob = URL.createObjectURL(svgBlob);

                        const img = new Image();
                        img.onload = () => {
                            const canvas = document.createElement("canvas");
                            canvas.width = img.width;
                            canvas.height = img.height;

                            const ctx = canvas.getContext("2d");
                            ctx.drawImage(img, 0, 0);

                            const base64 = canvas.toDataURL("image/png");
                            URL.revokeObjectURL(urlBlob);
                            resolve(base64);
                        };
                        img.onerror = reject;
                        img.src = urlBlob;
                    })
                    .catch(reject);
            } else {
                // Manejar imágenes normales (JPG, PNG, etc.)
                const img = new Image();
                img.crossOrigin = "anonymous"; // Importante para evitar problemas de CORS
                
                img.onload = () => {
                    const canvas = document.createElement("canvas");
                    canvas.width = img.width;
                    canvas.height = img.height;

                    const ctx = canvas.getContext("2d");
                    ctx.drawImage(img, 0, 0);

                    const base64 = canvas.toDataURL("image/jpeg", 0.8); // Calidad 0.8 para JPG
                    resolve(base64);
                };
                
                img.onerror = (error) => {
                    console.error(`Error al cargar imagen: ${url}`, error);
                    reject(error);
                };
                
                img.src = url;
            }
        }),
        // Timeout para evitar que se cuelgue
        new Promise((_, reject) => 
            setTimeout(() => reject(new Error('Timeout al cargar imagen')), timeout)
        )
    ]);
};

const generatePDF = async () => {
    try {
        if (!prestamoData.value || !prestamoData.value.data) {
            console.error("No hay datos de préstamo disponibles");
            return;
        }

        const data = prestamoData.value.data;

        const pdf = new jsPDF({ unit: "mm", format: "a4" });
        const margin = 10;
        const pageWidth = 210;
        const pageHeight = pdf.internal.pageSize.getHeight();
        const centerX = pageWidth / 2;
        let y = 15;

        const now = new Date();
        const fechaActual = now.toLocaleDateString("es-PE");
        const horaActual = now.toLocaleTimeString("es-PE");

        // Fecha y hora
        pdf.setFontSize(7);
        pdf.setFont("helvetica", "normal");
        pdf.text(`Fecha: ${fechaActual}    Hora: ${horaActual}`, centerX, y, { align: "center" });
        y += 5;
        
        pdf.setLineWidth(0.3);
        pdf.line(margin, y, pageWidth - margin, y);
        y += 2;

        if (data.logo) {
            try {
                const base64Logo = await loadImageAsBase64(data.logo);

                const logoWidth = 30;
                const logoHeight = 8;
                const logoX = margin + 10; // <-- mover logo un poco más adentro
                const logoY = y;

                pdf.addImage(base64Logo, 'PNG', logoX, logoY, logoWidth, logoHeight);

                // INFO a la derecha del logo
                const infoWidth = 120; // ancho del bloque de info
                const spaceUsedByLogo = logoX + logoWidth;
                const remainingWidth = pageWidth - spaceUsedByLogo - margin;

                const infoX = spaceUsedByLogo + (remainingWidth - infoWidth) / 2;
                const labelY = logoY + 2.5;
                const valueY = labelY + 5;

                // Etiquetas en negrita
                pdf.setFontSize(9);
                pdf.setFont("helvetica", "bold");
                pdf.text("Nombre propiedad", infoX, labelY);
                pdf.text("Esquema", infoX + 40, labelY);
                pdf.text("Plazo", infoX + 75, labelY);
                pdf.text("Monto otorgado", infoX + 105, labelY);

                // Valores también en negrita
                pdf.setFontSize(9);
                pdf.setFont("helvetica", "bold");
                pdf.text(data.Property ?? '---', infoX, valueY);
                pdf.text(data.Esquema ?? '---', infoX + 40, valueY);
                pdf.text(data.Plazo ?? '---', infoX + 75, valueY);
                const formattedMonto = data.Monto ? `S/ ${parseFloat(data.Monto).toFixed(2)}` : '---';
                pdf.text(formattedMonto, infoX + 105, valueY);

                y = valueY + 1;
            } catch (error) {
                console.error('Error al cargar logo:', error);
            }
        }


        pdf.setLineWidth(0.1);
        pdf.line(margin, y + 4, pageWidth - margin, y + 4);
        y += 10;

        pdf.setFontSize(8);
        pdf.setFont("helvetica", "normal");

        pdf.setFontSize(10);
        pdf.setFont("helvetica", "bold");
        pdf.text("Codigo de la subasta ", margin, y);
        y += 9;

        pdf.setFontSize(8);
        pdf.setFont("helvetica", "normal");

        pdf.setFontSize(10);
        pdf.setFont("helvetica", "bold");
        pdf.text("Ocupacion y/o carrera", margin, y); // título
        y += 5;

        pdf.setFontSize(9);
        pdf.setFont("helvetica", "normal");
        pdf.text(data.ocupacion_profesion ?? '---', margin, y); // valor debajo
        y += 7;

        pdf.setFontSize(10);
        pdf.setFont("helvetica", "bold");
        pdf.text("Descripcion del prestamo", margin, y); // título
        y += 5;

        pdf.setFontSize(9);
        pdf.setFont("helvetica", "normal");
        pdf.text(data.descripcion_financiamiento ?? '---', margin, y); // valor debajo
        y += 7;

        pdf.setFontSize(10);
        pdf.setFont("helvetica", "bold");
        pdf.text("Solicita el prestamo para:", margin, y); // título
        y += 5;

        pdf.setFontSize(9);
        pdf.setFont("helvetica", "normal");
        pdf.text(data.motivo_prestamo ?? '---', margin, y); // valor debajo
        y += 7;

        const indentX = margin + 30; // <-- sangría adicional

        pdf.setFontSize(8);
        pdf.setFont("helvetica", "normal");

        pdf.setFontSize(10);
        pdf.setFont("helvetica", "bold");
        pdf.text("Propiedad ofrecida en garantía", indentX, y);
        y += 5;

        pdf.setFontSize(9);
        pdf.setFont("helvetica", "normal");
        pdf.text(data.garantia ?? '---', indentX, y);
        y += 7;

        pdf.setFontSize(8);
        pdf.setFont("helvetica", "normal");

        pdf.setFontSize(10);
        pdf.setFont("helvetica", "bold");
        pdf.text("Perfil de riesgo", indentX, y);
        y += 5;

        pdf.setFontSize(9);
        pdf.setFont("helvetica", "normal");
        pdf.text(data.perfil_riesgo ?? '---', indentX, y);
        y += 7;

        pdf.setFontSize(8);
        pdf.setFont("helvetica", "normal");

        // SECCIÓN DE IMÁGENES
        pdf.setFontSize(10);
        pdf.setFont("helvetica", "normal");
        pdf.text("FOTOS DE LA PROPIEDAD", margin, y);
        y += 5;

        // Cargar y mostrar imágenes
        if (data.imagenes && data.imagenes.length > 0) {
            const imageWidth = 30; // Ancho de cada imagen en mm
            const imageHeight = 30; // Alto de cada imagen en mm
            const imagesPerRow = 4;
            const imageSpacing = 5; // Espacio entre imágenes
            const availableWidth = pageWidth - 2 * margin;
            const totalImageWidth = imagesPerRow * imageWidth + (imagesPerRow - 1) * imageSpacing;
            const startX = margin + (availableWidth - totalImageWidth) / 2;

            let currentRow = 0;
            let currentCol = 0;

            console.log(`Iniciando carga de ${data.imagenes.length} imágenes`);

            for (let i = 0; i < data.imagenes.length; i++) {
                try {
                    // Verificar si necesitamos una nueva página
                    const currentY = y + currentRow * (imageHeight + imageSpacing);
                    if (currentY + imageHeight > pageHeight - margin - 20) {
                        pdf.addPage();
                        y = margin + 10;
                        currentRow = 0;

                        // Reimprimimos el título en la nueva página
                        pdf.setFontSize(10);
                        pdf.setFont("helvetica", "bold");
                        pdf.text("FOTOS DE LA PROPIEDAD (Continuación)", margin, y);
                        y += 9;
                    }

                    const imageUrl = data.imagenes[i];
                    console.log(`Cargando imagen ${i + 1}/${data.imagenes.length}: ${imageUrl}`);
                    
                    const base64Image = await loadImageAsBase64(imageUrl);

                    const xPos = startX + currentCol * (imageWidth + imageSpacing);
                    const yPos = y + currentRow * (imageHeight + imageSpacing);

                    // Agregar imagen al PDF
                    pdf.addImage(base64Image, 'JPEG', xPos, yPos, imageWidth, imageHeight);

                    // Agregar borde opcional
                    pdf.setLineWidth(0.1);
                    pdf.setDrawColor(0,0,0);
                    pdf.rect(xPos, yPos, imageWidth, imageHeight);

                    console.log(`Imagen ${i + 1} cargada exitosamente`);

                    currentCol++;
                    if (currentCol >= imagesPerRow) {
                        currentCol = 0;
                        currentRow++;
                    }
                } catch (error) {
                    console.error(`Error al cargar imagen ${i + 1}:`, error);
                    console.log(`URL problemática: ${data.imagenes[i]}`);
                    
                    // Agregar un placeholder para la imagen que falló
                    const xPos = startX + currentCol * (imageWidth + imageSpacing);
                    const yPos = y + currentRow * (imageHeight + imageSpacing);
                    
                    // Dibujar un rectángulo con texto
                    pdf.setFillColor(240, 240, 240);
                    pdf.rect(xPos, yPos, imageWidth, imageHeight, 'F');
                    pdf.setDrawColor(200, 200, 200);
                    pdf.rect(xPos, yPos, imageWidth, imageHeight);
                    
                    pdf.setFontSize(6);
                    pdf.setTextColor(100, 100, 100);
                    pdf.text("Imagen no", xPos + imageWidth/2, yPos + imageHeight/2 - 2, { align: "center" });
                    pdf.text("disponible", xPos + imageWidth/2, yPos + imageHeight/2 + 2, { align: "center" });
                    pdf.setTextColor(0, 0, 0);
                    
                    // Continuar con la siguiente imagen
                    currentCol++;
                    if (currentCol >= imagesPerRow) {
                        currentCol = 0;
                        currentRow++;
                    }
                }
            }

            // Calcular el espacio usado por las imágenes
            const totalRows = Math.ceil(data.imagenes.length / imagesPerRow);
            y += totalRows * (imageHeight + imageSpacing) + 10;

            console.log(`Procesamiento de imágenes completado`);
        } else {
            pdf.setFontSize(8);
            pdf.setFont("helvetica", "normal");
            pdf.text("No hay imágenes disponibles", margin, y);
            y += 10;
        }

        // Título "CRONOGRAMA DE PAGO"
        pdf.setFontSize(10);
        pdf.setFont("helvetica", "normal");
        pdf.text("CRONOGRAMA DE PAGO", margin, y);
        y += 2;

        // Línea superior
        const indent = 15; // sangría para líneas
        const textIndent = 30; // sangría adicional solo para texto
        const innerMargin = margin + indent;
        const innerTextMargin = margin + textIndent;

        pdf.setLineWidth(0.1);
        pdf.line(innerMargin, y + 4, pageWidth - innerMargin, y + 4);
        y += 8;

        // Texto
        pdf.setFontSize(8);
        pdf.setFont("helvetica", "normal");

        const tem = `${parseFloat(data.tem).toFixed(4)}%`;
        const tea = `${parseFloat(data.tea).toFixed(4)}%`;
        const gananciaTotal = data.ganancia_total ? `S/ ${parseFloat(data.ganancia_total).toFixed(2)}` : '---';
        const montoOtorgado = data.Monto ? `S/ ${parseFloat(data.Monto).toFixed(2)}` : '---';

        // Nuevas posiciones ajustadas
        const col1X = innerTextMargin;
        const col2X = centerX + textIndent;

        pdf.text(`Tasa Efectiva Mensual: ${tem}`, col1X, y);
        pdf.text(`Ganancia Total: ${gananciaTotal}`, col2X, y);
        y += 5;

        pdf.text(`Tasa Efectiva Anual: ${tea}`, col1X, y);
        pdf.text(`Monto Otorgado: ${montoOtorgado}`, col2X, y);

        // Línea inferior
        pdf.setLineWidth(0.1);
        pdf.line(innerMargin, y + 4, pageWidth - innerMargin, y + 4);
        y += 5;

        pdf.setLineWidth(0.1);
        pdf.line(margin, y + 4, pageWidth - margin, y + 4);
        y += 9;

        // ---- Cronograma de pagos ----
        const headers = [
            "Cuota", "Vencimiento", "Saldo inicial", "Intereses", "Capital", "Cuota neta", "Saldo final"
        ];
        const colWidths = [20, 30, 30, 25, 25, 30, 30];

        pdf.setFontSize(8);
        pdf.setFont("helvetica", "bold");
        let x = margin;
        headers.forEach((header, i) => {
            pdf.text(header, x + colWidths[i] / 2, y, { align: "center" });
            x += colWidths[i];
        });

        y += 3; 
        pdf.line(margin, y, pageWidth - margin, y);

        const cuotas = data.cronograma || [];

        pdf.setFont("helvetica", "normal");

        cuotas.forEach((item, index) => {
            const rowData = [
                item.cuota,
                item.vencimiento || '-',
                `S/ ${parseFloat(item.saldo_inicial).toFixed(2)}`,
                `S/ ${parseFloat(item.intereses).toFixed(2)}`,
                `S/ ${parseFloat(item.capital).toFixed(2)}`,
                `S/ ${parseFloat(item.total_cuota).toFixed(2)}`,
                `S/ ${parseFloat(item.saldo_final).toFixed(2)}`
            ];

            const cellLines = rowData.map((text, i) =>
                pdf.splitTextToSize(text, colWidths[i] - 2)
            );
            const maxLines = Math.max(...cellLines.map(lines => lines.length));
            const rowHeight = maxLines * 5;

            if (y + rowHeight > pageHeight - margin - 10) {
                pdf.addPage();
                y = margin + 10;

                // Reimprimir encabezado
                pdf.setFontSize(8);
                pdf.setFont("helvetica", "bold");
                let headerX = margin;
                headers.forEach((header, i) => {
                    pdf.text(header, headerX + colWidths[i] / 2, y, { align: "center" });
                    headerX += colWidths[i];
                });
                y += 4;
                pdf.line(margin, y, pageWidth - margin, y);
                y += 4;
            }

            let cellX = margin;
            pdf.setFontSize(7);
            pdf.setFont("helvetica", "normal");
            rowData.forEach((text, i) => {
                const lines = pdf.splitTextToSize(text, colWidths[i] - 2);
                lines.forEach((line, j) => {
                    pdf.text(line, cellX + colWidths[i] / 2, y + 3.5 + j * 4, { align: "center" });
                });
                cellX += colWidths[i];
            });

            y += rowHeight;
        });

        pdf.line(margin, y, pageWidth - margin, y);
        y += 5;

        // Pie de página
        const totalPages = pdf.internal.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            pdf.setPage(i);
            pdf.setFontSize(8);
            pdf.setTextColor(100, 100, 100);
            pdf.text(window.location.href, pageWidth - margin, 287, { align: "right" });
            pdf.text(`Página ${i} de ${totalPages}`, margin, 287, { align: "left" });
            pdf.text(`Ref: ${data.id}`, centerX, 287, { align: "center" });
        }

        const blob = pdf.output("blob");
        localPdfUrl.value = URL.createObjectURL(new Blob([blob], { type: "application/pdf" }));
        console.log("PDF generado correctamente con ID:", data.id);
    } catch (error) {
        console.error("Error al generar PDF:", error);
    }
};
</script>