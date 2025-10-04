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
                            
                            const scale = 4;
                            const originalWidth = img.width || 200;
                            const originalHeight = img.height || 200;
                            
                            canvas.width = originalWidth * scale;
                            canvas.height = originalHeight * scale;

                            const ctx = canvas.getContext("2d");
                            
                            ctx.imageSmoothingEnabled = true;
                            ctx.imageSmoothingQuality = 'high';
                            
                            ctx.scale(scale, scale);
                            ctx.drawImage(img, 0, 0, originalWidth, originalHeight);

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
        const contentWidth = pageWidth - (2 * margin);
        let y = 0;

        // DETERMINAR LA MONEDA CORRECTA
        let moneda = 'S/';
        if (data.monto_general && data.monto_general.includes('$')) {
            moneda = '$';
        } else if (data.monto_general && data.monto_general.includes('S/')) {
            moneda = 'S/';
        }

        // FUNCIÓN PARA FORMATEAR NÚMEROS CON COMAS
        const formatNumberWithCommas = (number) => {
            if (typeof number === 'string') {
                const numericValue = parseFloat(number.replace(/[^\d.-]/g, ''));
                return isNaN(numericValue) ? '0.00' : numericValue.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
            }
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
        const totalInteresesNeto = totalInteresesBruto * 0.95;

        // ===== PRIMERA PÁGINA - INFORMACIÓN GENERAL =====
        
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

        // ENCABEZADO CON 6 COLUMNAS
        const headerStartX = 65;
        const colWidth = 24;
        const headerYOffset = 20;

        const colCenters = [
            headerStartX + colWidth / 2,
            headerStartX + colWidth * 1.5,
            headerStartX + colWidth * 2.5,
            headerStartX + colWidth * 3.5,
            headerStartX + colWidth * 4.5,
            headerStartX + colWidth * 5.5,
        ];

        // Títulos en azul
        pdf.setFontSize(8);
        pdf.setFont('helvetica', 'bold');
        pdf.setTextColor(103, 144, 255);

        pdf.text('Importe a', colCenters[0], headerYOffset - 3, { align: 'center' });
        pdf.text('Financiar', colCenters[0], headerYOffset, { align: 'center' });

        pdf.text('Rentabilidad', colCenters[1], headerYOffset - 3, { align: 'center' });
        pdf.text('Anual (TEA)', colCenters[1], headerYOffset, { align: 'center' });

        pdf.text('Tipo de', colCenters[2], headerYOffset - 3, { align: 'center' });
        pdf.text('Cronograma', colCenters[2], headerYOffset, { align: 'center' });

        pdf.text('Plazo', colCenters[3], headerYOffset - 2, { align: 'center' });

        pdf.text('Ratio LTV', colCenters[4], headerYOffset - 2, { align: 'center' });

        pdf.text('Riesgo', colCenters[5], headerYOffset - 2, { align: 'center' });

        // Valores en negro - USANDO DATOS REALES DEL JSON
        pdf.setFontSize(9);
        pdf.setFont("helvetica", "bold");
        pdf.setTextColor(0, 0, 0);
        
        const importe = data.monto_general || 'N/A';
        const teaOriginal = parseFloat(data.tea_raw) || 0;
        const rentabilidadAnual = `${teaOriginal.toFixed(1)}%`;
        const plazo = data.plazo || 'N/A';
        const esquema = data.esquema || 'N/A';
        
        const montoTasacion = data.monto_tasacion || 0;
        const montoPrestamo = data.monto_prestamo || 0;
        const ratioLTV = montoTasacion > 0 ? `${((montoPrestamo / montoTasacion) * 100).toFixed(1)}%` : '0%';

        const riesgo = data.riesgo || 'N/A';
        const riskImagePath = getRiskImage(riesgo);

        const valuesYOffset = headerYOffset + 7;
        pdf.text(importe.toString(), colCenters[0], valuesYOffset, { align: 'center' });
        pdf.text(rentabilidadAnual, colCenters[1], valuesYOffset, { align: 'center' });
        pdf.text(esquema.toString(), colCenters[2], valuesYOffset, { align: 'center' });
        pdf.text(plazo.toString(), colCenters[3], valuesYOffset, { align: 'center' });
        pdf.text(ratioLTV.toString(), colCenters[4], valuesYOffset, { align: 'center' });

        if (riskImagePath) {
            try {
                const base64Risk = await loadImageAsBase64(riskImagePath);
                const riskImgSize = 6;
                const offsetX = 0;
                const offsetY = 2;
                const riskX = colCenters[5] - riskImgSize / 2 + offsetX;
                const riskY = valuesYOffset - riskImgSize / 2 + offsetY;
                pdf.addImage(base64Risk, 'PNG', riskX, riskY, riskImgSize, riskImgSize);
            } catch (error) {
                console.error('Error al cargar imagen de riesgo:', error);
                pdf.text(riesgo.toString(), colCenters[5], valuesYOffset, { align: 'center' });
            }
        } else {
            pdf.text(riesgo.toString(), colCenters[5], valuesYOffset, { align: 'center' });
        }

        // LÍNEA SEPARADORA
        const lineY = headerYOffset + 2;
        const totalHeaderWidth = colWidth * 6;
        const lineStartX = headerStartX;
        const lineEndX = headerStartX + totalHeaderWidth;
        pdf.setLineWidth(0.3);
        pdf.setDrawColor(0, 0, 0);
        pdf.line(lineStartX, lineY, lineEndX, lineY);
        
        y = 50;

        const col1X = margin;
        const sectionWidth = (contentWidth / 2) - 5;
        const col2X = col1X + sectionWidth + 5;

        const addSection = (title, content, x, startY, width) => {
            // Título
            pdf.setFontSize(11);
            pdf.setFont("helvetica", "bold");
            pdf.setTextColor(255, 102, 51);
            pdf.text(title, x, startY);

            const titleWidth = pdf.getTextWidth(title);
            const lineY = startY + 1;
            pdf.setDrawColor(255, 102, 51);
            pdf.setLineWidth(0.3);
            pdf.line(x, lineY, x + titleWidth, lineY);

            // Contenido
            pdf.setFontSize(8);
            pdf.setTextColor(0, 0, 0);
            const lines = pdf.splitTextToSize(content || 'N/A', width);
            let currentY = startY + 6;

            lines.forEach(line => {
                const [label, value] = line.split(":");
                if (label && value !== undefined) {
                    pdf.setFont("helvetica", "bold");
                    pdf.text(label + ":", x, currentY);
                    const labelWidth = pdf.getTextWidth(label + ": ");
                    pdf.setFont("helvetica", "normal");
                    pdf.text(value.trim(), x + labelWidth, currentY);
                } else {
                    pdf.setFont("helvetica", "normal");
                    pdf.text(line, x, currentY);
                }
                currentY += 4;
            });

            return currentY + 4;
        };

        // ===== SECCIÓN QUE SE REPITE POR CADA PROPIEDAD =====
        const propiedades = data.propiedades || [];
        
        // Procesar cada propiedad
        for (let propIndex = 0; propIndex < propiedades.length; propIndex++) {
            const propiedad = propiedades[propIndex];
            
            // Nueva página para cada propiedad después de la primera
            if (propIndex > 0) {
                pdf.addPage();
                y = margin;
            }
            const initialY = y;

            // ===== SECCIONES QUE SE REPITEN POR PROPIEDAD =====
            
            // SOLO PARA LA PRIMERA PROPIEDAD: Mostrar todas las secciones
            if (propIndex === 0) {
                // Sobre el solicitante - EXACTAMENTE como viene en el JSON
                const solicitanteContent = `Profesión u Ocupación: ${data.profesion_ocupacion || 'N/A'}\nFuente de Ingresos: ${data.fuente_ingreso || 'N/A'}\nIngreso Promedio: ${data.ingreso_promedio || 'N/A'}\nRiesgo: ${data.riesgo || 'N/A'}`;
                const solicitanteEndY = addSection("Sobre el solicitante:", solicitanteContent, col1X, initialY, sectionWidth);

                // Sobre la garantía - USANDO LOS DATOS DE LA PROPIEDAD ACTUAL
                const garantiaContent = `Tipo de inmueble: ${propiedad.tipo_inmueble || 'N/A'}\nValor de tasación: ${moneda} ${data.monto_tasacion || 'N/A'}\nDetalle: ${propiedad.descripcion || 'N/A'}\nLa propiedad pertenece a: ${propiedad.pertenece || 'N/A'}`;
                const garantiaEndY = addSection("Sobre la garantía:", garantiaContent, col1X, solicitanteEndY + 1, sectionWidth);

                // Sobre el financiamiento - EXACTAMENTE como viene en el JSON
                const financiamientoContent = `Destino del financiamiento: ${data.motivo_prestamo || 'N/A'}\nDetalle: ${data.descripcion_financiamiento || 'N/A'}`;
                const financiamientoEndY = addSection("Sobre el financiamiento:", financiamientoContent, col2X, initialY, sectionWidth);

                y = Math.max(garantiaEndY, financiamientoEndY);
            } else {
                // PARA PROPIEDADES SUBSECUENTES: Solo mostrar "Sobre la garantía"
                const garantiaContent = `Tipo de inmueble: ${propiedad.tipo_inmueble || 'N/A'}\nValor de tasación: ${moneda} ${data.monto_tasacion || 'N/A'}\nDetalle: ${propiedad.descripcion || 'N/A'}\nLa propiedad pertenece a: ${propiedad.pertenece || 'N/A'}`;
                y = addSection("Sobre la garantía:", garantiaContent, col1X, initialY, sectionWidth);
            }

            y += 2;

            // ===== SECCIÓN DE FOTOS CON DESCRIPCIONES =====
            pdf.setFontSize(12);
            pdf.setFont('helvetica', 'bold');
            pdf.setTextColor(255, 102, 51);
            pdf.text('Fotos de la propiedad', col1X, y);
            y += 10;

            // Configuración para las fotos - SOLO 3 FOTOS + PRINCIPAL
            const photoSize = 50;
            const descriptionHeight = 15;
            const spacing = 10;
            const photosPerRow = 3; // Solo una fila con 3 fotos
            const sectionPadding = 8;

            // Calcular dimensiones de la sección completa
            const totalRowWidth = photoSize * photosPerRow + spacing * (photosPerRow - 1);
            const photoSectionWidth = totalRowWidth + sectionPadding * 2;
            const sectionHeight = photoSize + descriptionHeight + sectionPadding * 2;

            // Verificar si cabe en la página actual
            if (y + sectionHeight > pageHeight - 30) {
                pdf.addPage();
                y = margin;
            }

            const sectionX = margin + (contentWidth - photoSectionWidth) / 2;

            // Dibujar fondo beige de toda la sección
            pdf.setFillColor(237, 234, 228);
            pdf.rect(sectionX, y, photoSectionWidth, sectionHeight, 'F');

            const photoStartX = sectionX + sectionPadding;

            // Obtener las imágenes específicas de ESTA propiedad (máximo 3)
            const imagenesPropiedad = propiedad.imagenes || [];
            const imagenesValidas = imagenesPropiedad
                .filter((img) => img && img.url && img.url !== 'no-image.png')
                .slice(0, 3); // Solo tomar las primeras 3 imágenes

            // UNA SOLA FILA - 3 fotos con descripciones
            for (let i = 0; i < 3; i++) {
                const photoX = photoStartX + i * (photoSize + spacing);
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
                        pdf.text('Sin imagen', frameX + frameSize / 2, frameY + frameSize / 2, { align: 'center' });
                    }

                    // Agregar descripción debajo de la imagen
                    pdf.setFontSize(6);
                    pdf.setTextColor(0, 0, 0);
                    pdf.setFont('helvetica', 'normal');
                    const descripcion = imagenesValidas[i].descripcion || 'Sin descripción';
                    const descripcionLines = pdf.splitTextToSize(descripcion, photoSize);
                    let descY = photoY + photoSize + 2;
                    descripcionLines.forEach((line) => {
                        pdf.text(line, photoX + photoSize / 2, descY, { align: 'center' });
                        descY += 3;
                    });
                } else {
                    pdf.setFontSize(6);
                    pdf.setTextColor(150, 150, 150);
                    pdf.text('Sin imagen', frameX + frameSize / 2, frameY + frameSize / 2, { align: 'center' });
                    
                    // Descripción para placeholder
                    pdf.setFontSize(6);
                    pdf.setTextColor(150, 150, 150);
                    pdf.text('Sin imagen', photoX + photoSize / 2, photoY + photoSize + 4, { align: 'center' });
                }
            }

            // IMAGEN PRINCIPAL EN LA PARTE INFERIOR CENTRADA
            const principalY = y + sectionHeight + 10;

            if (data.principal) {
                try {
                    const base64Principal = await loadImageAsBase64(data.principal);
                    const principalWidth = 60;
                    const principalHeight = 40;
                    const principalX = margin + (contentWidth - principalWidth) / 2;
                    
                    pdf.addImage(base64Principal, 'PNG', principalX, principalY, principalWidth, principalHeight);
                    
                    // Descripción de la imagen principal
                    pdf.setFontSize(8);
                    pdf.setTextColor(0, 0, 0);
                    pdf.setFont('helvetica', 'bold');
                    
                } catch (error) {
                    console.error('Error al cargar imagen principal:', error);
                    pdf.setFontSize(10);
                    pdf.setFont('helvetica', 'bold');
                    pdf.setTextColor(150, 150, 150);
                    pdf.text('LOGO PRINCIPAL', margin + contentWidth / 2, principalY + 20, { align: 'center' });
                }
            }

            y = principalY + 50; // Ajustar Y para continuar
            pdf.setTextColor(0, 0, 0);
        }

        // ===== ÚLTIMA PÁGINA - CRONOGRAMA =====
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

        const tea = teaOriginal.toFixed(1);
        const montoOtorgado = data.monto_requerido || '0';

        const lineSpacing = 8;
        const colSpacing = 90;

        // ---- NUEVA DISPOSICIÓN EN 2 FILAS ---
        
        // PRIMERA FILA
        const row1Y = y;
        
        // Columna 1: Monto a Financiar
        let text1 = `Monto a Financiar: `;
        let text2 = `${montoOtorgado}`;
        let startX1 = margin + 20;

        pdf.setFont("helvetica", "bold");
        pdf.text(text1, startX1, row1Y);
        let x1 = startX1 + pdf.getTextWidth(text1);
        pdf.setFont("helvetica", "normal");
        pdf.text(text2, x1, row1Y);

        // Columna 2: Ganancia Bruta
        text1 = `GANANCIA BRUTA: `;
        text2 = `${moneda} ${formatNumberWithCommas(totalInteresesBruto)}`;
        let startX2 = startX1 + colSpacing;

        pdf.setFont("helvetica", "bold");
        pdf.text(text1, startX2, row1Y);
        let x2 = startX2 + pdf.getTextWidth(text1);
        pdf.setFont("helvetica", "normal");
        pdf.text(text2, x2, row1Y);

        // SEGUNDA FILA
        const row2Y = row1Y + lineSpacing;

        // Columna 1: Tasa efectiva Anual
        text1 = `Tasa efectiva Anual: `;
        text2 = `${tea}%`;

        pdf.setFont("helvetica", "bold");
        pdf.text(text1, startX1, row2Y);
        x1 = startX1 + pdf.getTextWidth(text1);
        pdf.setFont("helvetica", "normal");
        pdf.text(text2, x1, row2Y);

        // Columna 2: Ganancia Neta
        text1 = `GANANCIA NETA: `;
        text2 = `${moneda} ${formatNumberWithCommas(totalInteresesNeto)}`;

        pdf.setFont("helvetica", "bold");
        pdf.text(text1, startX2, row2Y);
        x2 = startX2 + pdf.getTextWidth(text1);
        pdf.setFont("helvetica", "normal");
        pdf.text(text2, x2, row2Y);

        // TERCERA FILA
        const row3Y = row2Y + lineSpacing;

        text1 = `Impuesto de 2da Categoría (5%): `;
        text2 = `${moneda} ${formatNumberWithCommas(totalInteresesBruto * 0.05)}`;

        pdf.setFont('helvetica', 'bold');
        pdf.text(text1, startX2, row3Y);
        x2 = startX2 + pdf.getTextWidth(text1);
        pdf.setFont('helvetica', 'normal');
        pdf.text(text2, x2, row3Y);

        // Actualizamos Y para continuar
        y = row3Y + lineSpacing + 5;

        // FUNCIÓN PARA CREAR HEADER DE TABLA
        const drawTableHeader = (startY) => {
            const headers = ["Cuota", "Saldo inicial", "Intereses", "Amortización", "Cuota"];
            const colWidths = [30, 35, 35, 35, 35];
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
                `S/ ${item.saldo_inicial || '0.00'}`,
                `S/ ${item.intereses || '0.00'}`,
                `S/ ${item.capital || '0.00'}`,
                `S/ ${item.total_cuota || '0.00'}`
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