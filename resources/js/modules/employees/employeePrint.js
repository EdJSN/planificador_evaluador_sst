//Generar pdf con lista de asistentes y dar formato azlo
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

// Función para obtener fecha al momento de generar el código
const today = new Date();
const formattedDate = `${String(today.getDate()).padStart(2, '0')}/${String(today.getMonth() + 1).padStart(2, '0')}/${today.getFullYear()}`;

export function setupEmployeePrint() {
    const printBtn = document.getElementById('printEmployeesBtn');
    const table = document.getElementById('employeesTable');

    if (!printBtn || !table) return;

    printBtn.addEventListener('click', () => {
        const doc = new jsPDF();
        const logo = new Image();
        logo.src = '/images/logoAzloSide.png';

        //Formato del pdf
        logo.onload = () => {
            const body = [];

            // Seccción: encabezado
            body.push([{
                content: 'CONTROL DE ASISTENCIA', colSpan: 12, styles: { fillColor: [5, 190, 192], fontStyle: 'bold', halign: 'center', textColor: [255, 255, 255] } }
            ]);
            body.push([
                { content: '', rowSpan: 2, colSpan: 3 }, // LOGO
                { content: 'Código', colSpan: 2, styles: { fontStyle: 'bold', halign: 'center', fontSize: 7, textColor: [5, 190, 192] } },
                { content: 'Versión', colSpan: 1, styles: { fontStyle: 'bold', halign: 'center', fontSize: 7, textColor: [5, 190, 192] } },
                { content: 'Fecha', colSpan: 2, styles: { fontStyle: 'bold', halign: 'center', fontSize: 6, textColor: [5, 190, 192] } },
                { content: 'Elaborado por', colSpan: 2, styles: { fontStyle: 'bold', halign: 'center', fontSize: 6, textColor: [5, 190, 192] } },
                { content: 'Aprobado por', colSpan: 2, styles: { fontStyle: 'bold', halign: 'center', fontSize: 6, textColor: [5, 190, 192] } }
            ]);
            body.push([
                // Espacio vacío para el logo
                { content: 'FO-SST-01', colSpan: 2, styles: { halign: 'center', fontSize: 6, textColor: [5, 190, 192] } },
                { content: '2', colSpan: 1, styles: { halign: 'center', fontSize: 6, textColor: [5, 190, 192] } },
                { content: '17/03/2020', colSpan: 2, styles: { halign: 'center', fontSize: 6, textColor: [5, 190, 192] } },
                { content: 'Responsable SGI', colSpan: 2, styles: { halign: 'center', fontSize: 6, textColor: [5, 190, 192] } },
                { content: 'Gerente general', colSpan: 2, styles: { halign: 'center', fontSize: 6, textColor: [5, 190, 192] } },
            ]);

            // Sección: información general
            body.push([{ content: 'I. Información general', colSpan: 12, styles: { fillColor: [5, 190, 192], fontStyle: 'bold', halign: 'center', textColor: [255, 255, 255] } }]);
            body.push([
                { content: 'Fecha', colSpan: 1, styles: { fillColor: [220, 220, 220], halign: 'center', fontSize: 10 } },
                { content: formattedDate, colSpan: 2, styles: { halign: 'center', fontSize: 10 } },
                { content: 'Hora inicio', colSpan: 1, styles: { fillColor: [220, 220, 220], halign: 'center', fontSize: 8, overflow: 'ellipsize' } },
                { content: '', colSpan: 1 },
                { content: 'Hora fin', colSpan: 1, styles: { fillColor: [220, 220, 220], halign: 'center', fontSize: 10 } },
                { content: '', colSpan: 1 },
                { content: 'Lugar', colSpan: 1, styles: { fillColor: [220, 220, 220], halign: 'center', fontSize: 10 } },
                { content: '', colSpan: 4 }
            ]);
            body.push([
                { content: 'Facilitador', colSpan: 2, styles: { fillColor: [220, 220, 220], halign: 'center', fontSize: 10 } },
                { content: '', colSpan: 2 },
                { content: 'Documento', colSpan: 2, styles: { fillColor: [220, 220, 220], halign: 'center', fontSize: 10 } },
                { content: '', colSpan: 2 },
                { content: 'Firma', colSpan: 2, styles: { fillColor: [220, 220, 220], halign: 'center', fontSize: 10 } },
                { content: '', colSpan: 2 }
            ]);
            body.push([
                { content: 'Tipo de evento', colSpan: 2, styles: { fillColor: [220, 220, 220], halign: 'center', fontSize: 10 } },
                { content: 'Capacitación ( )   Reunión ( )   Inducción ( )   Re inducción ( )', colSpan: 7, styles: { halign: 'center', fontSize: 10 } },
                { content: 'Otro:', colSpan: 3, styles: { halign: 'left', fontSize: 10 } },
            ]);
            body.push([
                { content: 'Tema', colSpan: 2, styles: { fillColor: [220, 220, 220], halign: 'center', fontSize: 10 } },
                { content: '', colSpan: 10 }
            ]);
            body.push([
                { content: 'Puntos tratados', colSpan: 12, styles: { fillColor: [220, 220, 220], halign: 'center', fontSize: 10 } }
            ]);
            for (let i = 0; i < 3; i++) {
                body.push([{ content: '', colSpan: 12 }]);
            }

            // Sección: asisteentes
            body.push([{ content: 'II. Asistentes', colSpan: 12, styles: { fillColor: [5, 190, 192], fontStyle: 'bold', halign: 'center', textColor: [255, 255, 255] } }]);
            body.push([
                { content: 'Nombres', colSpan: 4, styles: { fontStyle: 'bold', fillColor: [220, 220, 220], halign: 'center' } },
                { content: 'Documento', colSpan: 2, styles: { fontStyle: 'bold', fillColor: [220, 220, 220], halign: 'center' } },
                { content: 'Cargo', colSpan: 4, styles: { fontStyle: 'bold', fillColor: [220, 220, 220], halign: 'center' } },
                { content: 'Firma', colSpan: 2, styles: { fontStyle: 'bold', fillColor: [220, 220, 220], halign: 'center' } }
            ]);
            // DATOS DE TABLA HTML
            table.querySelectorAll('tbody tr').forEach(row => {
                const cells = row.querySelectorAll('td');
                body.push([
                    { content: cells[0]?.innerText || '', colSpan: 4 },
                    { content: cells[1]?.innerText || '', colSpan: 2, styles: { halign: 'center' } },
                    { content: cells[2]?.innerText || '', colSpan: 4 },
                    { content: '', colSpan: 2 } // Espacio vacío para firma
                ]);
            });

            // Sección: observaciones
            body.push([{ content: 'III. Observaciones', colSpan: 12, styles: { fillColor: [5, 190, 192], fontStyle: 'bold', halign: 'center', textColor: [255, 255, 255] } }]);
            for (let i = 0; i < 3; i++) {
                body.push([{ content: '', colSpan: 12 }]);
            }

            // Calcular ancho tabla
            const pageWidth = doc.internal.pageSize.getWidth();
            const margin = 10;
            const usablePageWidth = pageWidth - margin * 2;
            const numCols = 12;
            const colWidth = usablePageWidth / numCols;

            const columnStyles = {};
            for (let i = 0; i < numCols; i++) {
                columnStyles[i] = { cellWidth: colWidth };
            }

            // generar pdf
            autoTable(doc, {
                startY: 10,
                body: body,
                theme: 'grid',
                tableWidth: '100%', 
                margin: { left: 10, right: 10 },
                styles: {
                    fontSize: 10,
                    valign: 'middle',
                    lineColor: [0, 0, 0],
                },
                columnStyles: columnStyles,
                didDrawCell: (data) => {
                    if (data.row.index === 1 && data.column.index === 0) {
                        const imgWidth = 35;
                        const imgHeight = 15;
                        const x = data.cell.x + (data.cell.width - imgWidth) / 2;
                        const y = data.cell.y + (data.cell.height - imgHeight) / 2;
                        doc.addImage(logo, 'PNG', x, y, imgWidth, imgHeight);
                    }
                },
                //Texto de autorización
                didDrawPage: (data) => {
                    const text = 'Autorizo a GRUPO AZLO SAS BIC en el manejo y tratamiento de mis datos personales.';
                    doc.setFontSize(8);
                    doc.setTextColor(50);
                    doc.text(text, doc.internal.pageSize.getWidth() / 2, data.cursor.y + 10, { align: 'center' });
                }
            });

            doc.save('Control_Asistencia.pdf');
        };
    });
}
