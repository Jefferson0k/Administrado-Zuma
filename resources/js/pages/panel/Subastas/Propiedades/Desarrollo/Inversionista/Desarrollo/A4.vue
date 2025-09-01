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
                            
                            // ALTA RESOLUCIÓN - Escalar por 4x para mejor calidad
                            const scale = 4;
                            const originalWidth = img.width || 200;
                            const originalHeight = img.height || 200;
                            
                            canvas.width = originalWidth * scale;
                            canvas.height = originalHeight * scale;

                            const ctx = canvas.getContext("2d");
                            
                            // Configuraciones para máxima calidad
                            ctx.imageSmoothingEnabled = true;
                            ctx.imageSmoothingQuality = 'high';
                            
                            // Fondo blanco para mejor contraste
                            ctx.fillStyle = 'white';
                            ctx.fillRect(0, 0, canvas.width, canvas.height);
                            
                            // Escalar el contexto para dibujar a alta resolución
                            ctx.scale(scale, scale);
                            ctx.drawImage(img, 0, 0, originalWidth, originalHeight);

                            // Convertir a JPEG con máxima calidad
                            const base64 = canvas.toDataURL("image/jpeg", 1.0);
                            URL.revokeObjectURL(urlBlob);
                            resolve(base64);
                        };
                        img.onerror = (error) => {
                            console.error('Error al cargar SVG:', error);
                            URL.revokeObjectURL(urlBlob);
                            reject(error);
                        };
                        img.src = urlBlob;
                    })
                    .catch(reject);
            } else {
                const img = new Image();
                img.crossOrigin = "anonymous";
                
                img.onload = () => {
                    const canvas = document.createElement("canvas");
                    
                    // Para imágenes normales también aplicar alta resolución
                    const scale = 2;
                    canvas.width = img.width * scale;
                    canvas.height = img.height * scale;

                    const ctx = canvas.getContext("2d");
                    ctx.imageSmoothingEnabled = true;
                    ctx.imageSmoothingQuality = 'high';
                    
                    ctx.scale(scale, scale);
                    ctx.drawImage(img, 0, 0);

                    const base64 = canvas.toDataURL("image/jpeg", 0.95);
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
        let y = 0;

        // ===== PRIMERA PÁGINA - DISEÑO ESPECÍFICO =====
        
        if (data.logo) {
            try {
                const base64Hipotecas = await loadImageAsBase64(data.logo);
                const imgWidth = 210;
                const imgHeight = 40;
                pdf.addImage(base64Hipotecas, 'JPEG', margin + -15, y, imgWidth, imgHeight);
            } catch (error) {
                console.error('Error al cargar logo hipotecas:', error);
                pdf.setFontSize(24);
                pdf.setFont("helvetica", "bold");
                pdf.setTextColor(100, 100, 100);
                pdf.text("Hipotecas", margin, y + 10);
            }
        }

        // INFORMACIÓN EN EL HEADER (lado derecho)
        const headerStartX = 70;
        const colWidth = 23;

        // Encabezados en azul
        pdf.setFontSize(8);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(103, 144, 255); // Azul
        pdf.text("Rentabilidad Anual:", headerStartX, 18);
        pdf.text("ESQUEMA:", headerStartX + (colWidth * 1.2), 18);
        pdf.text("Plazo (meses):", headerStartX + (colWidth * 2.4), 18);
        pdf.text("Ratio LTV:", headerStartX + (colWidth * 3.6), 18);
        pdf.text("Riesgo:", headerStartX + (colWidth * 4.8), 18);
        pdf.text("MONTO:", headerStartX + (colWidth * 6), 18);

        // Valores en negro - todos en cero
        pdf.setFontSize(9);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        const rentabilidadAnual = '0';
        const esquema = '0';
        const plazo = '0';
        const ratioLTV = '0';
        const riesgo = '0';
        const monto = '0';

        pdf.text(pdf.splitTextToSize(rentabilidadAnual, colWidth - 2), headerStartX, 25);
        pdf.text(pdf.splitTextToSize(esquema, colWidth - 2), headerStartX + (colWidth * 1.2), 25);
        pdf.text(pdf.splitTextToSize(plazo, colWidth - 2), headerStartX + (colWidth * 2.4), 25);
        pdf.text(pdf.splitTextToSize(ratioLTV, colWidth - 2), headerStartX + (colWidth * 3.6), 25);
        pdf.text(pdf.splitTextToSize(riesgo, colWidth - 2), headerStartX + (colWidth * 4.8), 25);
        pdf.text(pdf.splitTextToSize(monto, colWidth - 2), headerStartX + (colWidth * 6), 25);

        // LÍNEA SEPARADORA DEBAJO DE LOS VALORES - mejor distribuida
        const lineY = 27; 
        const totalHeaderWidth = colWidth * 6.5;
        const lineStartX = headerStartX;
        const lineEndX = headerStartX + totalHeaderWidth;

        pdf.setLineWidth(0.3);
        pdf.setDrawColor(0, 0, 0);
        pdf.line(lineStartX, lineY, lineEndX, lineY);
        y = 55;

        const col1X = margin;
        const col2X = centerX + 5;
        const sectionWidth = (contentWidth / 2) - 5;

        const addSection = (title, content, x, startY, width) => {
            pdf.setFontSize(11);
            pdf.setFont("helvetica", "bold");
            pdf.setTextColor(255, 102, 51);
            pdf.text(title, x, startY);
            
            pdf.setFontSize(8);
            pdf.setFont("helvetica", "normal");
            pdf.setTextColor(0, 0, 0);
            const lines = pdf.splitTextToSize(content || '0', width);
            let currentY = startY + 6;
            lines.forEach(line => {
                pdf.text(line, x, currentY);
                currentY += 4;
            });
            
            return currentY + 4;
        };

        const initialY = y;

        const solicitanteContent = `Profesión u ocupación: ${data.ocupacion_profesion || '0'}\n\nIngresos mensuales promedio: ${data.inversionista?.documento || '0'}\n\nRiesgo: ${data.riesgo || '0'}`;
        const solicitanteEndY = addSection("Sobre el solicitante:", solicitanteContent, col1X, initialY, sectionWidth);

        const garantiaContent = `Tipo de inmueble: ${data.Property || '0'}\n\nUbicación: ${data.garantia || '0'}\n\nDescripción de la garantía: ${data.garantia || '0'}`;
        const garantiaEndY = addSection("Sobre la garantía:", garantiaContent, col1X, solicitanteEndY + 1, sectionWidth);

        const financiamientoContent = `Importe del financiamiento: ${data.Monto?.amount || '0'}\n\nMoneda del financiamiento: ${data.Monto?.currency || '0'}\n\nPlazo: ${data.Plazo || '0'}\n\nSistema de amortización: ${data.Esquema || '0'}\n\nDestino de fondos: ${data.solicitud_prestamo_para || '0'}\n\nTasa efectiva anual: ${data.tea || '0'}%\n\nTotal de intereses proyectados: ${data.tem || '0'}%`;
        const financiamientoEndY = addSection("Sobre el financiamiento:", financiamientoContent, col2X, initialY, sectionWidth);

        y = Math.max(garantiaEndY, financiamientoEndY);
        y += 2;
        
        // SECCIÓN DE FOTOS
        pdf.setFontSize(12);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(255, 102, 51);
        pdf.text("Fotos de la propiedad", col1X, y);
        y += 10;

        pdf.setFontSize(8);
        pdf.setTextColor(0, 0, 0);
        
        // ===== SEGUNDA PÁGINA - CRONOGRAMA =====
        pdf.addPage();
        
        y = 5;
        
        // LOGO HIPOTECAS
        if (data.hipotecas) {
            try {
                const base64Hipotecas = await loadImageAsBase64(data.hipotecas);
                const imgWidth = 210;
                const imgHeight = 70;
                pdf.addImage(base64Hipotecas, 'JPEG', margin + -15, y, imgWidth, imgHeight);
            } catch (error) {
                console.error('Error al cargar logo hipotecas:', error);
                pdf.setFontSize(24);
                pdf.setFont("helvetica", "bold");
                pdf.setTextColor(100, 100, 100);
                pdf.text("Hipotecas", margin, y + 20);
            }
        }

        y = 75;
        
        pdf.setFontSize(11);
        pdf.setTextColor(0, 0, 0);

        const tem = data.tem || '0';
        const tea = data.tea || '0';
        const garantiaTotal = data.Monto?.amount || '0';
        const montoOtorgado = data.Monto?.amount || '0';

        const lineSpacing = 8;
        const sidePadding = -5;

        // ---- Línea 1 ----
        let text1 = `Tasa efectiva Mensual: `;
        let text2 = `${tem}%      Garantía Total: `;
        let text3 = `${garantiaTotal}`;

        let line1 = text1 + text2 + text3;
        let textWidth = pdf.getTextWidth(line1);
        let startX = (pageWidth - textWidth) / 2 + sidePadding;

        pdf.setFont("helvetica", "bold");
        pdf.text(text1, startX, y);

        let x1 = startX + pdf.getTextWidth(text1);
        pdf.setFont("helvetica", "normal");
        pdf.text(tem + '%', x1, y);

        let x2 = x1 + pdf.getTextWidth(tem + "%      ");
        pdf.setFont("helvetica", "bold");
        pdf.text("Garantía Total: ", x2, y);

        let x3 = x2 + pdf.getTextWidth("Garantía Total: ");
        pdf.setFont("helvetica", "normal");
        pdf.text(garantiaTotal, x3, y);

        y += lineSpacing;

        // ---- Línea 2 ----
        text1 = `Tasa efectiva Anual: `;
        text2 = `${tea}%      Monto Otorgado: `;
        text3 = `${montoOtorgado}`;

        line1 = text1 + text2 + text3;
        textWidth = pdf.getTextWidth(line1);
        startX = (pageWidth - textWidth) / 2 + sidePadding;

        pdf.setFont("helvetica", "bold");
        pdf.text(text1, startX, y);

        x1 = startX + pdf.getTextWidth(text1);
        pdf.setFont("helvetica", "normal");
        pdf.text(tea + '%', x1, y);

        x2 = x1 + pdf.getTextWidth(tea + "%      ");
        pdf.setFont("helvetica", "bold");
        pdf.text("Monto Otorgado: ", x2, y);

        x3 = x2 + pdf.getTextWidth("Monto Otorgado: ");
        pdf.setFont("helvetica", "normal");
        pdf.text(montoOtorgado, x3, y);

        y += lineSpacing;

        // FUNCIÓN PARA CREAR HEADER DE TABLA (REUTILIZABLE)
        const drawTableHeader = (startY) => {
            const headers = ["Cuota", "Vencimiento", "Saldo inicial", "Intereses", "Capital", "Cuota Neta", "Saldo final"];
            const colWidths = [20, 25, 28, 26, 26, 28, 30];
            const totalTableWidth = colWidths.reduce((sum, width) => sum + width, 0);
            const tableStartX = margin + (contentWidth - totalTableWidth) / 2;
            const headerHeight = 12;

            // Fondo azul del header (MISMO COLOR que el original)
            pdf.setFillColor(103, 144, 255); // ✅ Color consistente
            pdf.rect(tableStartX, startY, totalTableWidth, headerHeight, 'F');

            // Líneas negras arriba y abajo del header
            pdf.setDrawColor(0, 0, 0);
            pdf.setLineWidth(0.5);
            pdf.line(tableStartX, startY, tableStartX + totalTableWidth, startY); // Línea superior
            pdf.line(tableStartX, startY + headerHeight, tableStartX + totalTableWidth, startY + headerHeight); // Línea inferior

            // Texto del header
            pdf.setFontSize(9);
            pdf.setFont("helvetica", "bold");
            pdf.setTextColor(255, 255, 255);

            let headerX = tableStartX;
            headers.forEach((header, i) => {
                pdf.text(header, headerX + colWidths[i] / 2, startY + 7, { align: "center" });
                headerX += colWidths[i];
            });

            return { tableStartX, totalTableWidth, colWidths, headerHeight };
        };

        // Crear el header inicial
        const tableConfig = drawTableHeader(y);
        const { tableStartX, totalTableWidth, colWidths, headerHeight } = tableConfig;
        
        y += headerHeight; // Avanzar después del header inicial

        // Restablecer color para el contenido
        pdf.setTextColor(0, 0, 0);
        pdf.setFont("helvetica", "normal");
        pdf.setFontSize(8);

        // Filas de datos del cronograma
        const cuotas = data.cronograma || [];

        cuotas.forEach((item, index) => {
            const rowData = [
                item.cuota?.toString() || (index + 1).toString(),
                item.vencimiento || '0',
                item.saldo_inicial || '0',
                item.intereses || '0',
                item.capital || '0',
                item.total_cuota || '0',
                item.saldo_final || '0'
            ];

            const rowHeight = 5;

            // Nueva página si es necesario
            if (y + rowHeight > pageHeight - 30) {
                pdf.addPage();
                y = margin + 10; // ✅ Posición consistente
                
                // Reimprimir header con configuración idéntica
                drawTableHeader(y);
                
                y += headerHeight; // ✅ Solo el headerHeight, sin espaciado extra
                
                // Restaurar configuración de texto para las filas
                pdf.setTextColor(0, 0, 0);
                pdf.setFont("helvetica", "normal");
                pdf.setFontSize(8);
            }

            // Dibujar SOLO el texto de la fila (SIN fondos, SIN líneas, SIN bordes)
            let cellX = tableStartX;
            rowData.forEach((text, i) => {
                pdf.text(text, cellX + colWidths[i]/2, y + 6, { align: "center" });
                cellX += colWidths[i];
            });

            y += rowHeight;
        });

        // PIE DE PÁGINA PARA TODAS LAS PÁGINAS
        const totalPages = pdf.internal.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            pdf.setPage(i);
            pdf.setFontSize(7);
            pdf.setTextColor(100, 100, 100);
            
            const footerY = pageHeight - 8;
            pdf.text(window.location.href, pageWidth - margin, footerY, { align: "right" });
            pdf.text(`Página ${i} de ${totalPages}`, margin, footerY);
            
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