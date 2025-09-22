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
                            
                            // NO agregar fondo blanco para SVGs - mantener transparencia
                            
                            // Escalar el contexto para dibujar a alta resolución
                            ctx.scale(scale, scale);
                            ctx.drawImage(img, 0, 0, originalWidth, originalHeight);

                            // Convertir a PNG para mantener transparencia
                            const base64 = canvas.toDataURL("image/png", 1.0);
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

        // DETERMINAR LA MONEDA CORRECTA
        let moneda = '$'; // Por defecto
        if (data.Monto && data.Monto.includes('$')) {
            moneda = '$';
        } else if (data.Monto && data.Monto.includes('S/')) {
            moneda = 'S/';
        } else if (data.Monto && data.Monto.includes('PEN')) {
            moneda = 'PEN';
        } else if (data.Moneda) {
            moneda = data.Moneda;
        }

        // FUNCIÓN PARA FORMATEAR NÚMEROS CON COMAS
        const formatNumberWithCommas = (number) => {
            return number.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        };

        // FUNCIÓN PARA OBTENER IMAGEN DE RIESGO
        const getRiskImage = (riesgo) => {
            const riskMap = {
                'A+': 'Amas.png',
                'A': 'A.png', 
                'B': 'B.png',
                'C': 'C.png'
            };
            return riskMap[riesgo] ? `/imagenes/riesgos/${riskMap[riesgo]}` : null;
        };

        // CALCULAR INTERESES BRUTO Y NETO
        const cronograma = data.cronograma || [];
        const totalInteresesBruto = cronograma.reduce((sum, cuota) => {
            const intereses = parseFloat(cuota.intereses) || 0;
            return sum + intereses;
        }, 0);
        const totalInteresesNeto = totalInteresesBruto * 0.95; // 95% del bruto (5% menos)

        // ===== PRIMERA PÁGINA - DISEÑO ESPECÍFICO =====
        
        if (data.logo) {
            try {
                const base64Hipotecas = await loadImageAsBase64(data.logo);
                const imgWidth = 215;
                const imgHeight = 45;
                pdf.addImage(base64Hipotecas, 'JPEG', margin + -20, y, imgWidth, imgHeight);
            } catch (error) {
                console.error('Error al cargar logo hipotecas:', error);
                pdf.setFontSize(24);
                pdf.setFont("helvetica", "bold");
                pdf.setTextColor(100, 100, 100);
                pdf.text("Hipotecas", margin, y + 10);
            }
        }

        // ENCABEZADO CON 5 COLUMNAS
        const headerStartX = 75;
        const colWidth = 28; // Ajustado para 5 columnas
        const headerYOffset = 20;

        // Títulos en azul (5 columnas)
        pdf.setFontSize(8);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(103, 144, 255);
        pdf.text("Importe", headerStartX, headerYOffset);
        pdf.text("Rentabilidad Anual", headerStartX + colWidth, headerYOffset);
        pdf.text("Plazo", headerStartX + (colWidth * 2), headerYOffset);
        pdf.text("Ratio LTV", headerStartX + (colWidth * 3), headerYOffset);
        pdf.text("Riesgo", headerStartX + (colWidth * 4), headerYOffset);

        // Valores en negro - USANDO TEA ORIGINAL (SIN REDUCIR)
        pdf.setFontSize(9);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        
        // Extraer valores reales de los datos
        const importe = data.Monto || 'N/A';
        const teaOriginal = parseFloat(data.tea) || 0;
        const rentabilidadAnual = `${teaOriginal.toFixed(1)}%`; // TEA original sin reducir
        const plazo = data.Plazo || 'N/A';
        const ratioLTV = data.Valor_Estimado && data.Monto_raw 
            ? `${((data.Monto_raw / parseFloat(data.Valor_Estimado.replace(/[^\d.-]/g, ''))) * 100).toFixed(1)}%` 
            : '0%';

        // Para el riesgo, mostrar la imagen si está disponible, sino el texto
        const riesgo = data.riesgo || 'N/A';
        const riskImagePath = getRiskImage(riesgo);

        // Posicionar valores directamente debajo de cada encabezado (5 columnas)
        const valuesYOffset = headerYOffset + 7;
        pdf.text(pdf.splitTextToSize(importe, colWidth - 2), headerStartX, valuesYOffset);
        pdf.text(pdf.splitTextToSize(rentabilidadAnual, colWidth - 2), headerStartX + colWidth, valuesYOffset);
        pdf.text(pdf.splitTextToSize(plazo, colWidth - 2), headerStartX + (colWidth * 2), valuesYOffset);
        pdf.text(pdf.splitTextToSize(ratioLTV, colWidth - 2), headerStartX + (colWidth * 3), valuesYOffset);

        // Para el riesgo, agregar imagen más pequeña o texto
        if (riskImagePath) {
            try {
                const base64Risk = await loadImageAsBase64(riskImagePath);
                const riskImgSize = 6; 

                // --- Ajustes finos ---
                const offsetX = -9;   // mueve a la izquierda (-) o derecha (+)
                const offsetY = 1;    // mueve arriba (-) o abajo (+)

                const riskX = headerStartX + (colWidth * 4) + (colWidth - riskImgSize) / 2 + offsetX;
                const riskY = valuesYOffset - (riskImgSize * 0.75) + offsetY;

                pdf.addImage(base64Risk, 'PNG', riskX, riskY, riskImgSize, riskImgSize);
            } catch (error) {
                console.error('Error al cargar imagen de riesgo:', error);
                pdf.text(pdf.splitTextToSize(riesgo, colWidth - 2), headerStartX + (colWidth * 4), valuesYOffset);
            }
        } else {
            pdf.text(pdf.splitTextToSize(riesgo, colWidth - 2), headerStartX + (colWidth * 4), valuesYOffset);
        }


        // LÍNEA SEPARADORA DEBAJO DE LOS VALORES
        const lineY = headerYOffset + 2;
        const totalHeaderWidth = colWidth * 5; // Ajustado para 5 columnas
        const lineStartX = headerStartX;
        const lineEndX = headerStartX + totalHeaderWidth;

        pdf.setLineWidth(0.3);
        pdf.setDrawColor(0, 0, 0);
        pdf.line(lineStartX, lineY, lineEndX, lineY);
        
        y = 60;

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
            const lines = pdf.splitTextToSize(content || 'N/A', width);
            let currentY = startY + 6;
            lines.forEach(line => {
                pdf.text(line, x, currentY);
                currentY += 4;
            });
            
            return currentY + 4;
        };

        const initialY = y;

        // CONTENIDO CON DATOS REALES
        const solicitanteContent = `Profesión u ocupación: ${data.ocupacion_profesion || 'N/A'}\n\nIngresos mensuales promedio: ${data.inversionista?.documento || 'N/A'}\n\nRiesgo: ${data.riesgo || 'N/A'}`;
        const solicitanteEndY = addSection("Sobre el solicitante:", solicitanteContent, col1X, initialY, sectionWidth);

        // SECCIÓN DE GARANTÍA SIN "GARANTÍA TOTAL"
        const garantiaContent = `Tipo de inmueble: ${data.solicitud_prestamo_para || 'N/A'}\n\nValor estimado: ${data.Valor_Estimado || 'N/A'}`;
        const garantiaEndY = addSection("Sobre la garantía:", garantiaContent, col1X, solicitanteEndY + 1, sectionWidth);

        // USAR TEA ORIGINAL (SIN REDUCIR) EN LA SECCIÓN DE FINANCIAMIENTO
        const financiamientoContent = `Importe del financiamiento: ${data.Monto || 'N/A'}\n\nMoneda del financiamiento: ${moneda}\n\nPlazo: ${data.Plazo || 'N/A'}\n\nSistema de amortización: ${data.Esquema || 'N/A'}\n\nTasa efectiva anual: ${teaOriginal.toFixed(1)}%\n\nTotal de intereses proyectados (Bruto): ${moneda} ${formatNumberWithCommas(totalInteresesBruto)}`;
        const financiamientoEndY = addSection("Sobre el financiamiento:", financiamientoContent, col2X, initialY, sectionWidth);

        y = Math.max(garantiaEndY, financiamientoEndY);
        y += 2;
        
        // ===== SECCIÓN DE FOTOS CON DESCRIPCIONES =====
        pdf.setFontSize(12);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(255, 102, 51);
        pdf.text("Fotos de la propiedad", col1X, y);
        y += 10;

        // Configuración para las fotos con espacio para descripciones
        const photoSize = 45;
        const descriptionHeight = 15;
        const spacing = 10;
        const photosPerRow1 = 3;
        const photosPerRow2 = 2;
        const sectionPadding = 8;

        // Calcular dimensiones de la sección completa incluyendo descripciones
        const totalRowWidth1 = (photoSize * photosPerRow1) + (spacing * (photosPerRow1 - 1));
        const photoSectionWidth = totalRowWidth1 + (sectionPadding * 2);
        const sectionHeight = (photoSize * 2) + descriptionHeight * 2 + spacing + (sectionPadding * 2);
        const sectionX = margin + (contentWidth - photoSectionWidth) / 2;

        // Dibujar fondo beige de toda la sección
        pdf.setFillColor(237, 234, 228);
        pdf.rect(sectionX, y, photoSectionWidth, sectionHeight, 'F');

        const photoStartX1 = sectionX + sectionPadding;
        const photoStartX2 = photoStartX1;

        // Obtener las imágenes y filtrar las válidas
        const imagenes = data.imagenes || [];
        const imagenesValidas = imagenes.filter(img => img && img !== 'no-image.png');

        // PRIMERA FILA - 3 fotos con descripciones
        for (let i = 0; i < 3; i++) {
            const photoX = photoStartX1 + (i * (photoSize + spacing));
            const photoY = y + sectionPadding;
            
            const frameSize = photoSize * 0.85;
            const frameX = photoX + (photoSize - frameSize) / 2;
            const frameY = photoY + (photoSize - frameSize) / 2;
            
            pdf.setFillColor(255, 255, 255);
            pdf.rect(frameX, frameY, frameSize, frameSize, 'F');
            
            pdf.setDrawColor(0, 0, 0);
            pdf.setLineWidth(0.3);
            pdf.rect(frameX, frameY, frameSize, frameSize);
            
            if (i < imagenesValidas.length) {
                try {
                    const base64Image = await loadImageAsBase64(imagenesValidas[i].url);
                    
                    const imageSize = frameSize * 0.8;
                    const imageCenterX = frameX + (frameSize - imageSize) / 2;
                    const imageCenterY = frameY + (frameSize - imageSize) / 2;
                    
                    const format = imagenesValidas[i].url.toLowerCase().includes('.svg') ? 'PNG' : 'JPEG';
                    pdf.addImage(base64Image, format, imageCenterX, imageCenterY, imageSize, imageSize);
                    
                } catch (error) {
                    console.error(`Error al cargar imagen ${i + 1}:`, error);
                    pdf.setFontSize(6);
                    pdf.setTextColor(150, 150, 150);
                    pdf.text("Sin imagen", frameX + frameSize/2, frameY + frameSize/2, { align: "center" });
                }
                
                // Agregar descripción debajo de la imagen
                pdf.setFontSize(6);
                pdf.setTextColor(0, 0, 0);
                pdf.setFont("helvetica", "normal");
                const descripcion = imagenesValidas[i].descripcion || 'Sin descripción';
                const descripcionLines = pdf.splitTextToSize(descripcion, photoSize);
                let descY = photoY + photoSize + 2;
                descripcionLines.forEach(line => {
                    pdf.text(line, photoX + photoSize/2, descY, { align: "center" });
                    descY += 3;
                });
            } else {
                pdf.setFontSize(6);
                pdf.setTextColor(150, 150, 150);
                pdf.text("Sin imagen", frameX + frameSize/2, frameY + frameSize/2, { align: "center" });
            }
        }

        // SEGUNDA FILA - 2 fotos con descripciones
        const secondRowY = y + sectionPadding + photoSize + descriptionHeight + spacing;

        for (let i = 3; i < 5; i++) {
            const photoIndex = i - 3;
            const photoX = photoStartX2 + (photoIndex * (photoSize + spacing));
            const photoY = secondRowY;
            
            const frameSize = photoSize * 0.85;
            const frameX = photoX + (photoSize - frameSize) / 2;
            const frameY = photoY + (photoSize - frameSize) / 2;
            
            pdf.setFillColor(255, 255, 255);
            pdf.rect(frameX, frameY, frameSize, frameSize, 'F');
            
            pdf.setDrawColor(0, 0, 0);
            pdf.setLineWidth(0.3);
            pdf.rect(frameX, frameY, frameSize, frameSize);
            
            if (i < imagenesValidas.length) {
                try {
                    const base64Image = await loadImageAsBase64(imagenesValidas[i].url);
                    
                    const imageSize = frameSize * 0.8;
                    const imageCenterX = frameX + (frameSize - imageSize) / 2;
                    const imageCenterY = frameY + (frameSize - imageSize) / 2;
                    
                    const format = imagenesValidas[i].url.toLowerCase().includes('.svg') ? 'PNG' : 'JPEG';
                    pdf.addImage(base64Image, format, imageCenterX, imageCenterY, imageSize, imageSize);
                    
                } catch (error) {
                    console.error(`Error al cargar imagen ${i + 1}:`, error);
                    pdf.setFontSize(6);
                    pdf.setTextColor(150, 150, 150);
                    pdf.text("Sin imagen", frameX + frameSize/2, frameY + frameSize/2, { align: "center" });
                }
                
                // Agregar descripción debajo de la imagen
                pdf.setFontSize(6);
                pdf.setTextColor(0, 0, 0);
                pdf.setFont("helvetica", "normal");
                const descripcion = imagenesValidas[i].descripcion || 'Sin descripción';
                const descripcionLines = pdf.splitTextToSize(descripcion, photoSize);
                let descY = photoY + photoSize + 2;
                descripcionLines.forEach(line => {
                    pdf.text(line, photoX + photoSize/2, descY, { align: "center" });
                    descY += 3;
                });
            } else {
                pdf.setFontSize(6);
                pdf.setTextColor(150, 150, 150);
                pdf.text("Sin imagen", frameX + frameSize/2, frameY + frameSize/2, { align: "center" });
            }
        }

        // IMAGEN PRINCIPAL EN LA ESQUINA INFERIOR DERECHA
        const logoSize = photoSize * 0.85;
        const logoX = photoStartX1 + (2 * (photoSize + spacing));
        const logoY = secondRowY;

        if (data.principal) {
            try {
                const base64Principal = await loadImageAsBase64(data.principal);
                const frameX = logoX + (photoSize - logoSize) / 3;
                const frameY = logoY + (photoSize - logoSize) / 2;
                const imageCenterX = frameX + (logoSize * 0.1);
                const imageCenterY = frameY + (logoSize * 0.1);
                
                // Hacer la imagen un poco más ancha
                const finalImageWidth = logoSize * 1.0;
                const finalImageHeight = logoSize * 0.7;
                
                pdf.addImage(base64Principal, 'PNG', imageCenterX, imageCenterY, finalImageWidth, finalImageHeight);
            } catch (error) {
                console.error('Error al cargar imagen principal:', error);
                pdf.setFontSize(8);
                pdf.setFont("helvetica", "bold");
                pdf.setTextColor(0, 0, 0);
                pdf.text("LOGO", logoX + photoSize/2, logoY + photoSize/2, { align: "center" });
            }
        } else {
            pdf.setFontSize(8);
            pdf.setFont("helvetica", "bold");
            pdf.setTextColor(0, 0, 0);
            pdf.text("LOGO", logoX + photoSize/2, logoY + photoSize/2, { align: "center" });
        }
        
        y = y + sectionHeight + 10;
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
        const tea = teaOriginal.toFixed(1); // Usar TEA original
        const valorEstimado = data.Valor_Estimado || data.Monto || '0';
        const montoOtorgado = data.Monto || '0';

        const lineSpacing = 8;
        const sidePadding = -5;

        // ---- NUEVA DISPOSICIÓN EN 2 FILAS ----
        
        // PRIMERA FILA: Monto Otorgado | Tasa Efectiva Anual
        const row1Y = y;
        const colSpacing = 90; // Espacio entre columnas
        
        // --- FILA 1: Total de interés (Bruto) | Monto Otorgado ---

// Columna 1: Total de interés (Bruto)
let text1 = `Total de interés (Bruto): `;
let text2 = `${moneda} ${formatNumberWithCommas(totalInteresesBruto)}`;
let startX1 = margin + 20;

pdf.setFont("helvetica", "bold");
pdf.text(text1, startX1, row1Y);
let x1 = startX1 + pdf.getTextWidth(text1);
pdf.setFont("helvetica", "normal");
pdf.text(text2, x1, row1Y);

// Columna 2: Monto Otorgado
text1 = `Monto Otorgado: `;
text2 = `${montoOtorgado}`;
let startX2 = startX1 + colSpacing;

pdf.setFont("helvetica", "bold");
pdf.text(text1, startX2, row1Y);
let x2 = startX2 + pdf.getTextWidth(text1);
pdf.setFont("helvetica", "normal");
pdf.text(text2, x2, row1Y);


// --- FILA 2: Total de interés (Neto) | Tasa efectiva Anual ---
const row2Y = row1Y + lineSpacing;

// Columna 1: Total de interés (Neto)
text1 = `Total de interés (Neto): `;
text2 = `${moneda} ${formatNumberWithCommas(totalInteresesNeto)}`;

pdf.setFont("helvetica", "bold");
pdf.text(text1, startX1, row2Y);
x1 = startX1 + pdf.getTextWidth(text1);
pdf.setFont("helvetica", "normal");
pdf.text(text2, x1, row2Y);

// Columna 2: Tasa efectiva Anual
text1 = `Tasa efectiva Anual: `;
text2 = `${tea}%`;

pdf.setFont("helvetica", "bold");
pdf.text(text1, startX2, row2Y);
x2 = startX2 + pdf.getTextWidth(text1);
pdf.setFont("helvetica", "normal");
pdf.text(text2, x2, row2Y);


        y = row2Y + lineSpacing + 5;

        // FUNCIÓN PARA CREAR HEADER DE TABLA
        const drawTableHeader = (startY) => {
            const headers = ["Cuota", "Vencimiento", "Saldo inicial", "Intereses", "Capital", "Cuota Neta", "Saldo final"];
            const colWidths = [20, 25, 28, 26, 26, 28, 30];
            const totalTableWidth = colWidths.reduce((sum, width) => sum + width, 0);
            const tableStartX = margin + (contentWidth - totalTableWidth) / 2;
            const headerHeight = 12;

            // Fondo azul del header
            pdf.setFillColor(103, 144, 255);
            pdf.rect(tableStartX, startY, totalTableWidth, headerHeight, 'F');

            // Líneas negras arriba y abajo del header
            pdf.setDrawColor(0, 0, 0);
            pdf.setLineWidth(0.5);
            pdf.line(tableStartX, startY, tableStartX + totalTableWidth, startY);
            pdf.line(tableStartX, startY + headerHeight, tableStartX + totalTableWidth, startY + headerHeight);

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
        const { tableStartX, colWidths, headerHeight } = tableConfig;
        
        y += headerHeight;

        // Restablecer color para el contenido
        pdf.setTextColor(0, 0, 0);
        pdf.setFont("helvetica", "normal");
        pdf.setFontSize(8);

        // Filas de datos del cronograma
        const cuotas = data.cronograma || [];

        cuotas.forEach((item, index) => {
            const rowData = [
                item.cuota?.toString() || (index + 1).toString(),
                item.vencimiento || 'N/A',
                item.saldo_inicial_formatted || item.saldo_inicial || '0',
                item.intereses_formatted || item.intereses || '0',
                item.capital_formatted || item.capital || '0',
                item.total_cuota_formatted || item.total_cuota || '0',
                item.saldo_final || '0'
            ];

            const rowHeight = 5;

            // Nueva página si es necesario
            if (y + rowHeight > pageHeight - 30) {
                pdf.addPage();
                y = margin + 10;
                
                // Reimprimir header
                drawTableHeader(y);
                y += headerHeight;
                
                // Restaurar configuración de texto para las filas
                pdf.setTextColor(0, 0, 0);
                pdf.setFont("helvetica", "normal");
                pdf.setFontSize(8);
            }

            // Dibujar texto de la fila
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