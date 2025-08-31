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
        const headerStartX = 90;
        const colWidth = 30;

        // Encabezados en azul
        pdf.setFontSize(8);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(103, 144, 255); // Azul
        pdf.text("PROPIEDAD:", headerStartX, 18);
        pdf.text("ESQUEMA:", headerStartX + colWidth, 18);
        pdf.text("PLAZO:", headerStartX + (colWidth * 2), 18);
        pdf.text("MONTO:", headerStartX + (colWidth * 3), 18);

        // Valores en negro
        pdf.setFontSize(9);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        const propertyName = data.Property || '---';
        const esquema = data.Esquema || '---';
        const plazo = data.Plazo || '---';
        const monto = data.Monto?.amount ? `${parseFloat(data.Monto.amount).toLocaleString()}` : '---';

        pdf.text(pdf.splitTextToSize(propertyName, colWidth - 2), headerStartX, 25);
        pdf.text(pdf.splitTextToSize(esquema, colWidth - 2), headerStartX + colWidth, 25);
        pdf.text(pdf.splitTextToSize(plazo, colWidth - 2), headerStartX + (colWidth * 2), 25);
        pdf.text(pdf.splitTextToSize(monto, colWidth - 2), headerStartX + (colWidth * 3), 25);

        // LÍNEA SEPARADORA DEBAJO DE LOS VALORES (centrada y más fina)
        const lineY = 21; // Posición Y de la líne  a
        const totalHeaderWidth = colWidth * 4; // Ancho total del header
        const lineWidth = totalHeaderWidth * 0.8; // Línea al 80% del ancho total (más corta)
        const lineStartX = headerStartX + (totalHeaderWidth - lineWidth) / 50; // Centrada
        const lineEndX = lineStartX + lineWidth; // Final de la línea

        pdf.setLineWidth(0.2); // Línea más fina
        pdf.setDrawColor(0, 0, 0); // Color negro para la línea
        pdf.line(lineStartX, lineY, lineEndX, lineY); // Dibuja la línea horizontal
        y = 55;

        const col1X = margin;
        const col2X = centerX + 5;
        const sectionWidth = (contentWidth / 2) - 5;

        const addSection = (title, content, x, startY, width) => {
            // Título
            pdf.setFontSize(11);
            pdf.setFont("helvetica", "bold");
            pdf.setTextColor(255, 102, 51); // Naranja
            pdf.text(title, x, startY);
            
            // Contenido
            pdf.setFontSize(8);
            pdf.setFont("helvetica", "normal");
            pdf.setTextColor(0, 0, 0);
            const lines = pdf.splitTextToSize(content || '---', width);
            let currentY = startY + 6;
            lines.forEach(line => {
                pdf.text(line, x, currentY);
                currentY += 4;
            });
            
            return currentY + 4;
        };

        // Guardamos la posición Y inicial
        const initialY = y;

        // Columna izquierda: "Sobre el solicitante" arriba
        const solicitanteContent = `Profesión u ocupación: ${data.ocupacion_profesion || '---'}\n\nIngresos mensuales promedio: ${data.inversionista?.documento || '---'}\n\nRiesgo: ${data.riesgo || '---'}`;
        const solicitanteEndY = addSection("Sobre el solicitante:", solicitanteContent, col1X, initialY, sectionWidth);

        // Columna izquierda: "Sobre la garantía" abajo (después de un pequeño espacio)
        const garantiaContent = `Tipo de inmueble: ${data.Property || '---'}\n\nUbicación: ${data.garantia || '---'}\n\nDescripción de la garantía: ${data.garantia || '---'}`;
        const garantiaEndY = addSection("Sobre la garantía:", garantiaContent, col1X, solicitanteEndY + 1, sectionWidth);

        // Columna derecha: "Sobre el financiamiento" (ocupa toda la altura)
        const financiamientoContent = `Importe del financiamiento: ${data.Monto?.amount ? `S/${parseFloat(data.Monto.amount).toLocaleString()}` : '---'}\n\nMoneda del financiamiento: ${data.Monto?.currency || '---'}\n\nPlazo: ${data.Plazo || '---'}\n\nSistema de amortización: ${data.Esquema || '---'}\n\nDestino de fondos: ${data.solicitud_prestamo_para || '---'}\n\nTasa efectiva anual: ${data.tea ? data.tea + '%' : '---'}\n\nTotal de intereses proyectados: ${data.tem ? data.tem + '%' : '---'}`;
        const financiamientoEndY = addSection("Sobre el financiamiento:", financiamientoContent, col2X, initialY, sectionWidth);

        // Actualizamos la posición Y para el siguiente contenido
        y = Math.max(garantiaEndY, financiamientoEndY);

        y += 2; // Espaciado antes de las fotos
        
        // SECCIÓN DE FOTOS CON ALTA CALIDAD
        pdf.setFontSize(12);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(255, 102, 51);
        pdf.text("Fotos de la propiedad", col1X, y);
        y += 10;

        // PIE DE PÁGINA PRIMERA PÁGINA
        pdf.setFontSize(8);
        pdf.setTextColor(0, 0, 0);
        
        // ===== SEGUNDA PÁGINA - CRONOGRAMA =====
        pdf.addPage();
        
        // Header con fecha y hora (esquina superior derecha)
        const now = new Date();
        const fechaActual = now.toLocaleDateString("es-PE");
        const horaActual = now.toLocaleTimeString("es-PE", { 
            hour: '2-digit', 
            minute: '2-digit', 
            hour12: true 
        });
        
        y = 5;
        
        // LOGO HIPOTECAS CON MÁXIMA CALIDAD
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

        pdf.setFontSize(9);
        pdf.setFont("helvetica", "normal");
        pdf.setTextColor(0, 0, 0);
        pdf.text(`Fecha: ${fechaActual}`, pageWidth - margin, 15, { align: "right" });
        pdf.text(`Hora: ${horaActual}`, pageWidth - margin, 22, { align: "right" });

        y = 75;
        
        pdf.setFontSize(11);
        pdf.setTextColor(0, 0, 0);

        const tem = data.tem ? `${parseFloat(data.tem).toFixed(4)}%` : '3.2000%';
        const tea = data.tea ? `${parseFloat(data.tea).toFixed(4)}%` : '5.5000%';
        const garantiaTotal = data.Monto?.amount ? `${parseFloat(data.Monto.amount).toLocaleString()}` : 'xxxx';
        const montoOtorgado = data.Monto?.amount ? `${parseFloat(data.Monto.amount).toLocaleString()}` : 'xxxx';

        const lineSpacing = 8; // más abierto que 8

        // Margen lateral extra para que no se pegue tanto a los costados
        const sidePadding = -5;

        // ---- Línea 1 ----
        let text1 = `Tasa efectiva Mensual: `;
        let text2 = `${tem}      Garantía Total: `;
        let text3 = `${garantiaTotal}`;

        let line1 = text1 + text2 + text3;
        let textWidth = pdf.getTextWidth(line1);

        // centrado pero con padding lateral
        let startX = (pageWidth - textWidth) / 2 + sidePadding;

        pdf.setFont("helvetica", "bold");
        pdf.text(text1, startX, y);

        let x1 = startX + pdf.getTextWidth(text1);
        pdf.setFont("helvetica", "normal");
        pdf.text(tem, x1, y);

        let x2 = x1 + pdf.getTextWidth(tem + "      ");
        pdf.setFont("helvetica", "bold");
        pdf.text("Garantía Total: ", x2, y);

        let x3 = x2 + pdf.getTextWidth("Garantía Total: ");
        pdf.setFont("helvetica", "normal");
        pdf.text(garantiaTotal, x3, y);

        y += lineSpacing;

        // ---- Línea 2 ----
        text1 = `Tasa efectiva Anual: `;
        text2 = `${tea}      Monto Otorgado: `;
        text3 = `${montoOtorgado}`;

        line1 = text1 + text2 + text3;
        textWidth = pdf.getTextWidth(line1);
        startX = (pageWidth - textWidth) / 2 + sidePadding;

        pdf.setFont("helvetica", "bold");
        pdf.text(text1, startX, y);

        x1 = startX + pdf.getTextWidth(text1);
        pdf.setFont("helvetica", "normal");
        pdf.text(tea, x1, y);

        x2 = x1 + pdf.getTextWidth(tea + "      ");
        pdf.setFont("helvetica", "bold");
        pdf.text("Monto Otorgado: ", x2, y);

        x3 = x2 + pdf.getTextWidth("Monto Otorgado: ");
        pdf.setFont("helvetica", "normal");
        pdf.text(montoOtorgado, x3, y);

        y += lineSpacing;

        const headers = ["Cuota", "Vencimiento", "Saldo inicial", "Intereses", "Capital", "Cuota Neta", "Saldo final"];
        const colWidths = [20, 25, 28, 26, 26, 28, 30];

        const totalTableWidth = colWidths.reduce((sum, width) => sum + width, 0);
        const tableStartX = margin + (contentWidth - totalTableWidth) / 2;

        const headerHeight = 12;
        pdf.setFillColor(103, 144, 255); // Azul del diseño
        pdf.rect(tableStartX, y, totalTableWidth, headerHeight, 'F');

        pdf.setFontSize(9);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(255, 255, 255);

        let headerX = tableStartX;
        headers.forEach((header, i) => {
            pdf.text(header, headerX + colWidths[i] / 2, y + 7, { align: "center" });
            headerX += colWidths[i];
        });

        // 🔹 Líneas negras arriba y abajo del header
        pdf.setDrawColor(0, 0, 0); // Negro
        pdf.setLineWidth(0.5);

        // Línea superior
        pdf.line(tableStartX, y, tableStartX + totalTableWidth, y);

        // Línea inferior
        pdf.line(tableStartX, y + headerHeight, tableStartX + totalTableWidth, y + headerHeight);

        y += headerHeight; // avanzar después del header

        
        // Restablecer color para el contenido
        pdf.setTextColor(0, 0, 0);

        // Filas de datos del cronograma (COMPLETAMENTE SIN RAYAS/BORDES)
        const cuotas = data.cronograma || [];
        pdf.setFont("helvetica", "normal");
        pdf.setFontSize(8);

        cuotas.forEach((item, index) => {
            const rowData = [
                item.cuota?.toString() || (index + 1).toString(),
                item.vencimiento || '25/09/2015',
                item.saldo_inicial ? `${parseFloat(item.saldo_inicial).toLocaleString()}` : 'S/10,000',
                item.intereses ? `${parseFloat(item.intereses).toLocaleString()}` : 'S/10,000',
                item.capital ? `${parseFloat(item.capital).toLocaleString()}` : 'S/10,000',
                item.total_cuota ? `${parseFloat(item.total_cuota).toLocaleString()}` : 'S/10,000',
                item.saldo_final ? `${parseFloat(item.saldo_final).toLocaleString()}` : 'S/10,000'
            ];

            const rowHeight = 5; // Espaciado entre filas

            // Nueva página si es necesario
            if (y + rowHeight > pageHeight - 30) {
                pdf.addPage();
                y = margin + 20;
                
                // Reimprimir header en nueva página
                pdf.setFillColor(100, 149, 237);
                pdf.rect(tableStartX, y, totalTableWidth, headerHeight, 'F');
                
                pdf.setFontSize(9);
                pdf.setFont("helvetica", "bold");
                pdf.setTextColor(255, 255, 255);
                
                let reHeaderX = tableStartX;
                headers.forEach((header, i) => {
                    pdf.text(header, reHeaderX + colWidths[i]/2, y + 7, { align: "center" });
                    reHeaderX += colWidths[i];
                });
                
                y += headerHeight + 5;
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
            pdf.text(`Ref: ${data.id || 'REF-001'}`, centerX, footerY, { align: "center" });
            
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