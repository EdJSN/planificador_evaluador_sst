// Generar pdf con lista de asistentes y dar formato azlo
import jsPDF from 'jspdf';
import autoTable from 'jspdf-autotable';

export function setupAttendancePrint() {
    document.addEventListener("click", async (e) => {
        if (e.target.closest(".printBtn")) {
            const activityId = e.target.closest(".printBtn").dataset.id;
            const token = document.querySelector('input[name="_token"]').value;

            try {
                const response = await fetch("/check/print-attendees", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        "X-CSRF-TOKEN": token,
                    },
                    body: JSON.stringify({ activity_id: activityId }),
                });

                const result = await response.json();

                //Fecha estimada de ejecución
                const attendees = result.attendees;
                const estimatedDate = result.estimated_date;
                let formattedDate = '';
                if (estimatedDate) {
                    const dateObj = new Date(estimatedDate);
                    formattedDate = `${String(dateObj.getDate()).padStart(2, '0')}/${String(dateObj.getMonth() + 1).padStart(2, '0')}/${dateObj.getFullYear()}`;
                }

                //Tema de la actividad
                const topic = result.topic;

                const doc = new jsPDF();
                const logo = new Image();
                logo.src = '/images/logoAzloSide.png';

                logo.onload = () => {
                    const body = [];

                    // Sección encabezado
                    body.push([{ content: 'CONTROL DE ASISTENCIA', colSpan: 12, styles: { fillColor: [5, 190, 192], fontStyle: 'bold', halign: 'center', textColor: [255, 255, 255] } }]);
                    body.push([
                        { content: '', rowSpan: 2, colSpan: 3 }, // LOGO
                        { content: 'Código', colSpan: 2, styles: { fontStyle: 'bold', halign: 'center', fontSize: 7, textColor: [5, 190, 192] } },
                        { content: 'Versión', colSpan: 1, styles: { fontStyle: 'bold', halign: 'center', fontSize: 7, textColor: [5, 190, 192] } },
                        { content: 'Fecha', colSpan: 2, styles: { fontStyle: 'bold', halign: 'center', fontSize: 6, textColor: [5, 190, 192] } },
                        { content: 'Elaborado por', colSpan: 2, styles: { fontStyle: 'bold', halign: 'center', fontSize: 6, textColor: [5, 190, 192] } },
                        { content: 'Aprobado por', colSpan: 2, styles: { fontStyle: 'bold', halign: 'center', fontSize: 6, textColor: [5, 190, 192] } }
                    ]);
                    body.push([
                        { content: 'FO-SST-01', colSpan: 2, styles: { halign: 'center', fontSize: 6, textColor: [5, 190, 192] } },
                        { content: '2', colSpan: 1, styles: { halign: 'center', fontSize: 6, textColor: [5, 190, 192] } },
                        { content: '17/03/2020', colSpan: 2, styles: { halign: 'center', fontSize: 6, textColor: [5, 190, 192] } },
                        { content: 'Responsable SGI', colSpan: 2, styles: { halign: 'center', fontSize: 6, textColor: [5, 190, 192] } },
                        { content: 'Gerente general', colSpan: 2, styles: { halign: 'center', fontSize: 6, textColor: [5, 190, 192] } },
                    ]);

                    // Sección información general
                    body.push([{ content: 'I. Información general', colSpan: 12, styles: { fillColor: [5, 190, 192], fontStyle: 'bold', halign: 'center', textColor: [255, 255, 255] } }]);
                    body.push([
                        { content: 'Fecha', colSpan: 1, styles: { fillColor: [220, 220, 220], halign: 'center', fontSize: 10 } },
                        { content: formattedDate, colSpan: 2, styles: { halign: 'center', fontSize: 10 } },
                        { content: 'Hora inicio', colSpan: 1, styles: { fillColor: [220, 220, 220], halign: 'center', fontSize: 8 } },
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
                        { content: topic || '', colSpan: 10 }
                    ]);
                    body.push([
                        { content: 'Puntos tratados', colSpan: 12, styles: { fillColor: [220, 220, 220], halign: 'center', fontSize: 10 } }
                    ]);
                    for (let i = 0; i < 3; i++) {
                        body.push([{ content: '', colSpan: 12 }]);
                    }

                    // Sección asistentes
                    body.push([{ content: 'II. Asistentes', colSpan: 12, styles: { fillColor: [5, 190, 192], fontStyle: 'bold', halign: 'center', textColor: [255, 255, 255] } }]);
                    body.push([
                        { content: 'Nombres', colSpan: 4, styles: { fontStyle: 'bold', fillColor: [220, 220, 220], halign: 'center' } },
                        { content: 'Documento', colSpan: 2, styles: { fontStyle: 'bold', fillColor: [220, 220, 220], halign: 'center' } },
                        { content: 'Cargo', colSpan: 3, styles: { fontStyle: 'bold', fillColor: [220, 220, 220], halign: 'center' } },
                        { content: 'Firma', colSpan: 3, styles: { fontStyle: 'bold', fillColor: [220, 220, 220], halign: 'center' } }
                    ]);

                    attendees.forEach(att => {
                        body.push([
                            { content: att.name || '', colSpan: 4 },
                            { content: att.document || '', colSpan: 2 },
                            { content: att.position || '', colSpan: 3 },
                            { content: '', colSpan: 3 } // columna firma
                        ]);
                    });

                    autoTable(doc, {
                        startY: 20,
                        body: body,
                        theme: 'grid',
                        margin: { left: 10, right: 10 },
                        didDrawCell: (data) => {
                            // Dibujar firma en columna "Firma"
                            if (data.section === 'body' && data.column.index === 3) {
                                const rowIndex = data.row.index - 2; // ajusta por encabezados
                                const attendee = attendees[rowIndex];
                                if (attendee?.file_path) {
                                    doc.addImage(
                                        attendee.file_path,
                                        'PNG',
                                        data.cell.x + 2,
                                        data.cell.y + 2,
                                        40,
                                        12
                                    );
                                }
                            }
                        }
                    });

                    // Sección observaciones
                    body.push([{ content: 'III. Observaciones', colSpan: 12, styles: { fillColor: [5, 190, 192], fontStyle: 'bold', halign: 'center', textColor: [255, 255, 255] } }]);
                    for (let i = 0; i < 3; i++) {
                        body.push([{ content: '', colSpan: 12 }]);
                    }

                    // Calcular anchos
                    const pageWidth = doc.internal.pageSize.getWidth();
                    const margin = 10;
                    const usablePageWidth = pageWidth - margin * 2;
                    const numCols = 12;
                    const colWidth = usablePageWidth / numCols;

                    const columnStyles = {};
                    for (let i = 0; i < numCols; i++) {
                        columnStyles[i] = { cellWidth: colWidth };
                    }

                    autoTable(doc, {
                        startY: 10,
                        body: body,
                        theme: 'grid',
                        tableWidth: '100%',
                        margin: { left: 10, right: 10 },
                        styles: { fontSize: 10, valign: 'middle', lineColor: [0, 0, 0] },
                        columnStyles: columnStyles,
                        didDrawCell: (data) => {
                            // Logo en encabezado
                            if (data.row.index === 1 && data.column.index === 0) {
                                const imgWidth = 35;
                                const imgHeight = 15;
                                const x = data.cell.x + (data.cell.width - imgWidth) / 2;
                                const y = data.cell.y + (data.cell.height - imgHeight) / 2;
                                doc.addImage(logo, 'PNG', x, y, imgWidth, imgHeight);
                            }

                            // Firmas en sección asistentes
                            const asistentesStartRow = body.findIndex(r => r[0].content === 'II. Asistentes') + 2;
                            if (data.row.index >= asistentesStartRow) {
                                const attendee = attendees[data.row.index - asistentesStartRow];
                                if (attendee?.file_path && data.column.index >= 9 && data.column.index <= 11) {
                                    const sigWidth = data.cell.width - 3;
                                    const sigHeight = data.cell.height - 3;
                                    doc.addImage(
                                        attendee.file_path,
                                        'PNG',
                                        data.cell.x + 1,
                                        data.cell.y + 1,
                                        sigWidth,
                                        sigHeight
                                    );
                                }
                            }
                        },
                        didDrawPage: (data) => {
                            const text = 'Autorizo a GRUPO AZLO SAS BIC en el manejo y tratamiento de mis datos personales.';
                            doc.setFontSize(8);
                            doc.setTextColor(50);
                            doc.text(text, doc.internal.pageSize.getWidth() / 2, data.cursor.y + 10, { align: 'center' });
                        }
                    });

                    doc.save('Certificado_Asistencia_Digital.pdf');
                };

            } catch (err) {
                console.error("Error generando PDF:", err);
            }
        }
    });
}


