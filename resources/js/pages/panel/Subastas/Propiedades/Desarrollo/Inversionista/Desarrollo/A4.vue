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
            const isSvg = url.toLowerCase().includes('.svg') || url.includes('svg');
            
            if (isSvg) {
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
                const img = new Image();
                img.crossOrigin = "anonymous";
                
                img.onload = () => {
                    const canvas = document.createElement("canvas");
                    canvas.width = img.width;
                    canvas.height = img.height;

                    const ctx = canvas.getContext("2d");
                    ctx.drawImage(img, 0, 0);

                    const base64 = canvas.toDataURL("image/jpeg", 0.8);
                    resolve(base64);
                };
                
                img.onerror = (error) => {
                    console.error(`Error al cargar imagen: ${url}`, error);
                    reject(error);
                };
                
                img.src = url;
            }
        }),
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
        const margin = 15;
        const pageWidth = 210;
        const pageHeight = pdf.internal.pageSize.getHeight();
        const centerX = pageWidth / 2;
        const contentWidth = pageWidth - (2 * margin);
        let y = 20;

        const now = new Date();
        const fechaActual = now.toLocaleDateString("es-PE");
        const horaActual = now.toLocaleTimeString("es-PE");

        // HEADER: Fecha y hora centrada
        pdf.setFontSize(8);
        pdf.setFont("helvetica", "normal");
        pdf.text(`Fecha: ${fechaActual}    Hora: ${horaActual}`, centerX, y, { align: "center" });
        y += 8;
        
        // Línea separadora
        pdf.setLineWidth(0.3);
        pdf.line(margin, y, pageWidth - margin, y);
        y += 8;

        // LOGO Y INFORMACIÓN EN LA MISMA FILA
        if (data.logo) {
            try {
                const base64Logo = await loadImageAsBase64(data.logo);
                
                // Logo a la izquierda
                const logoWidth = 35;
                const logoHeight = 12;
                const logoX = margin;
                const logoY = y;
                
                pdf.addImage(base64Logo, 'PNG', logoX, logoY, logoWidth, logoHeight);

                // Información a la derecha del logo
                const infoStartX = logoX + logoWidth + 10;
                const colWidth = (pageWidth - infoStartX - margin) / 4;
                
                // Encabezados
                pdf.setFontSize(7);
                pdf.setFont("helvetica", "bold");
                pdf.text("PROPIEDAD", infoStartX, y + 2);
                pdf.text("ESQUEMA", infoStartX + colWidth, y + 2);
                pdf.text("PLAZO", infoStartX + (colWidth * 2), y + 2);
                pdf.text("MONTO", infoStartX + (colWidth * 3), y + 2);

                // Valores
                pdf.setFontSize(8);
                pdf.setFont("helvetica", "bold");
                
                // Texto con wrapping para cada columna
                const propertyText = pdf.splitTextToSize(data.Property || '---', colWidth - 2);
                const esquemaText = pdf.splitTextToSize(data.Esquema || '---', colWidth - 2);
                const plazoText = pdf.splitTextToSize(data.Plazo || '---', colWidth - 2);
                const montoText = pdf.splitTextToSize(data.Monto ? `S/ ${parseFloat(data.Monto).toFixed(2)}` : '---', colWidth - 2);

                let textY = y + 6;
                pdf.text(propertyText, infoStartX, textY);
                pdf.text(esquemaText, infoStartX + colWidth, textY);
                pdf.text(plazoText, infoStartX + (colWidth * 2), textY);
                pdf.text(montoText, infoStartX + (colWidth * 3), textY);

                // Calcular la altura máxima usada
                const maxLines = Math.max(propertyText.length, esquemaText.length, plazoText.length, montoText.length);
                y = logoY + Math.max(logoHeight, 6 + (maxLines * 4)) + 5;
                
            } catch (error) {
                console.error('Error al cargar logo:', error);
                y += 15;
            }
        }

        // Línea separadora
        pdf.setLineWidth(0.2);
        pdf.line(margin, y, pageWidth - margin, y);
        y += 10;

        // SECCIONES DE INFORMACIÓN
        const addSection = (title, content, indent = 0) => {
            const sectionX = margin + indent;
            const maxWidth = contentWidth - indent;
            
            // Verificar espacio disponible
            if (y > pageHeight - 30) {
                pdf.addPage();
                y = margin + 10;
            }
            
            // Título de sección
            pdf.setFontSize(9);
            pdf.setFont("helvetica", "bold");
            pdf.text(title, sectionX, y);
            y += 6;

            // Contenido
            pdf.setFontSize(8);
            pdf.setFont("helvetica", "normal");
            const lines = pdf.splitTextToSize(content || '---', maxWidth);
            
            lines.forEach(line => {
                if (y > pageHeight - 20) {
                    pdf.addPage();
                    y = margin + 10;
                }
                pdf.text(line, sectionX, y);
                y += 4;
            });
            
            y += 4; // Espacio entre secciones
        };

        // Código de subasta (vacío por ahora)
        addSection("Código de la subasta:", "");

        // Información del préstamo
        addSection("Ocupación y/o carrera:", data.ocupacion_profesion);
        addSection("Descripción del préstamo:", data.descripcion_financiamiento);
        addSection("Solicita el préstamo para:", data.motivo_prestamo);

        // Secciones con indentación
        addSection("Propiedad ofrecida en garantía:", data.garantia, 20);
        addSection("Perfil de riesgo:", data.perfil_riesgo, 20);

        y += 5;

        // SECCIÓN DE IMÁGENES
        if (y > pageHeight - 50) {
            pdf.addPage();
            y = margin + 10;
        }

        pdf.setFontSize(10);
        pdf.setFont("helvetica", "bold");
        pdf.text("FOTOS DE LA PROPIEDAD", margin, y);
        y += 8;

        if (data.imagenes && data.imagenes.length > 0) {
            const imageSize = 35;
            const imageSpacing = 8;
            const imagesPerRow = 4;
            const totalImageWidth = (imagesPerRow * imageSize) + ((imagesPerRow - 1) * imageSpacing);
            const startX = margin + (contentWidth - totalImageWidth) / 2;

            let currentRow = 0;
            let currentCol = 0;

            for (let i = 0; i < data.imagenes.length; i++) {
                const currentY = y + (currentRow * (imageSize + imageSpacing));
                
                // Verificar si necesitamos nueva página
                if (currentY + imageSize > pageHeight - margin - 20) {
                    pdf.addPage();
                    y = margin + 10;
                    currentRow = 0;
                    
                    pdf.setFontSize(10);
                    pdf.setFont("helvetica", "bold");
                    pdf.text("FOTOS DE LA PROPIEDAD (Continuación)", margin, y);
                    y += 8;
                }

                try {
                    const base64Image = await loadImageAsBase64(data.imagenes[i]);
                    const xPos = startX + (currentCol * (imageSize + imageSpacing));
                    const yPos = y + (currentRow * (imageSize + imageSpacing));

                    pdf.addImage(base64Image, 'JPEG', xPos, yPos, imageSize, imageSize);
                    pdf.setLineWidth(0.1);
                    pdf.setDrawColor(0, 0, 0);
                    pdf.rect(xPos, yPos, imageSize, imageSize);

                } catch (error) {
                    console.error(`Error al cargar imagen ${i + 1}:`, error);
                    
                    const xPos = startX + (currentCol * (imageSize + imageSpacing));
                    const yPos = y + (currentRow * (imageSize + imageSpacing));
                    
                    pdf.setFillColor(240, 240, 240);
                    pdf.rect(xPos, yPos, imageSize, imageSize, 'F');
                    pdf.setDrawColor(200, 200, 200);
                    pdf.rect(xPos, yPos, imageSize, imageSize);
                    
                    pdf.setFontSize(7);
                    pdf.setTextColor(100, 100, 100);
                    pdf.text("Imagen no", xPos + imageSize/2, yPos + imageSize/2 - 2, { align: "center" });
                    pdf.text("disponible", xPos + imageSize/2, yPos + imageSize/2 + 2, { align: "center" });
                    pdf.setTextColor(0, 0, 0);
                }

                currentCol++;
                if (currentCol >= imagesPerRow) {
                    currentCol = 0;
                    currentRow++;
                }
            }

            const totalRows = Math.ceil(data.imagenes.length / imagesPerRow);
            y += (totalRows * (imageSize + imageSpacing)) + 10;
        } else {
            pdf.setFontSize(8);
            pdf.setFont("helvetica", "normal");
            pdf.text("No hay imágenes disponibles", margin, y);
            y += 10;
        }

        // CRONOGRAMA DE PAGO
        if (y > pageHeight - 80) {
            pdf.addPage();
            y = margin + 10;
        }

        pdf.setFontSize(11);
        pdf.setFont("helvetica", "bold");
        pdf.text("CRONOGRAMA DE PAGO", margin, y);
        y += 8;

        // Información financiera en dos columnas
        const financialInfoY = y;
        const col1X = margin + 20;
        const col2X = centerX + 10;

        pdf.setFontSize(8);
        pdf.setFont("helvetica", "normal");

        const tem = data.tem ? `${parseFloat(data.tem).toFixed(4)}%` : '---';
        const tea = data.tea ? `${parseFloat(data.tea).toFixed(4)}%` : '---';
        const gananciaTotal = data.ganancia_total ? `S/ ${parseFloat(data.ganancia_total).toFixed(2)}` : '---';
        const montoOtorgado = data.Monto ? `S/ ${parseFloat(data.Monto).toFixed(2)}` : '---';

        pdf.text(`Tasa Efectiva Mensual: ${tem}`, col1X, y);
        pdf.text(`Ganancia Total: ${gananciaTotal}`, col2X, y);
        y += 5;

        pdf.text(`Tasa Efectiva Anual: ${tea}`, col1X, y);
        pdf.text(`Monto Otorgado: ${montoOtorgado}`, col2X, y);
        y += 8;

        // Líneas decorativas
        const indentMargin = margin + 10;
        pdf.setLineWidth(0.1);
        pdf.line(indentMargin, y, pageWidth - indentMargin, y);
        y += 3;
        pdf.line(margin, y, pageWidth - margin, y);
        y += 8;

        // TABLA DE CRONOGRAMA
        const headers = ["Cuota", "Vencimiento", "Saldo inicial", "Intereses", "Capital", "Cuota neta", "Saldo final"];
        const colWidths = [18, 25, 27, 22, 22, 25, 27];
        
        // Verificar que las columnas no excedan el ancho disponible
        const totalTableWidth = colWidths.reduce((sum, width) => sum + width, 0);
        const tableStartX = margin + (contentWidth - totalTableWidth) / 2;

        // Encabezados de tabla
        pdf.setFontSize(7);
        pdf.setFont("helvetica", "bold");
        let headerX = tableStartX;
        
        headers.forEach((header, i) => {
            pdf.text(header, headerX + colWidths[i]/2, y, { align: "center" });
            headerX += colWidths[i];
        });
        
        y += 4;
        pdf.setLineWidth(0.2);
        pdf.line(tableStartX, y, tableStartX + totalTableWidth, y);
        y += 2;

        // Filas de datos
        const cuotas = data.cronograma || [];
        pdf.setFont("helvetica", "normal");
        pdf.setFontSize(6);

        cuotas.forEach((item) => {
            const rowData = [
                item.cuota?.toString() || '-',
                item.vencimiento || '-',
                item.saldo_inicial ? `S/ ${parseFloat(item.saldo_inicial).toFixed(2)}` : '-',
                item.intereses ? `S/ ${parseFloat(item.intereses).toFixed(2)}` : '-',
                item.capital ? `S/ ${parseFloat(item.capital).toFixed(2)}` : '-',
                item.total_cuota ? `S/ ${parseFloat(item.total_cuota).toFixed(2)}` : '-',
                item.saldo_final ? `S/ ${parseFloat(item.saldo_final).toFixed(2)}` : '-'
            ];

            // Calcular altura de fila
            const cellLines = rowData.map((text, i) => pdf.splitTextToSize(text, colWidths[i] - 1));
            const maxLines = Math.max(...cellLines.map(lines => lines.length));
            const rowHeight = Math.max(4, maxLines * 3.5);

            // Nueva página si es necesario
            if (y + rowHeight > pageHeight - 25) {
                pdf.addPage();
                y = margin + 10;

                // Reimprimir encabezados
                pdf.setFontSize(7);
                pdf.setFont("helvetica", "bold");
                let reHeaderX = tableStartX;
                headers.forEach((header, i) => {
                    pdf.text(header, reHeaderX + colWidths[i]/2, y, { align: "center" });
                    reHeaderX += colWidths[i];
                });
                y += 4;
                pdf.line(tableStartX, y, tableStartX + totalTableWidth, y);
                y += 2;
                pdf.setFont("helvetica", "normal");
                pdf.setFontSize(6);
            }

            // Dibujar fila
            let cellX = tableStartX;
            rowData.forEach((text, i) => {
                const lines = pdf.splitTextToSize(text, colWidths[i] - 1);
                lines.forEach((line, lineIndex) => {
                    pdf.text(line, cellX + colWidths[i]/2, y + 3 + (lineIndex * 3.5), { align: "center" });
                });
                cellX += colWidths[i];
            });

            y += rowHeight;
        });

        // Línea final de tabla
        pdf.setLineWidth(0.2);
        pdf.line(tableStartX, y, tableStartX + totalTableWidth, y);

        // PIE DE PÁGINA PARA TODAS LAS PÁGINAS
        const totalPages = pdf.internal.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            pdf.setPage(i);
            pdf.setFontSize(7);
            pdf.setTextColor(100, 100, 100);
            
            const footerY = pageHeight - 8;
            pdf.text(window.location.href, pageWidth - margin, footerY, { align: "right" });
            pdf.text(`Página ${i} de ${totalPages}`, margin, footerY);
            pdf.text(`Ref: ${data.id || referenceNumber}`, centerX, footerY, { align: "center" });
            
            pdf.setTextColor(0, 0, 0);
        }

        // Generar blob y URL
        const blob = pdf.output("blob");
        localPdfUrl.value = URL.createObjectURL(new Blob([blob], { type: "application/pdf" }));
        console.log("PDF generado correctamente con ID:", data.id);
        
    } catch (error) {
        console.error("Error al generar PDF:", error);
    }
};
</script>