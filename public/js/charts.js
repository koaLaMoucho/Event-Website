const pieCtx = document.getElementById('myPieChart');

if (pieCtx) {
    const event_id = document.getElementById('event-charts').getAttribute('data-id');
    sendAjaxRequest('get', '/api/events/' + event_id + '/charts?' + encodeForAjax({ type: 'distribution' }), {}, pieCtx_handler);

    function pieCtx_handler() {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            const pieChartData = response.moucho;

            if (pieChartData) {
                new Chart(pieCtx, {
                    type: 'pie',
                    data: pieChartData,
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            title: {
                                display: true,
                                text: 'Distribution of Tickets by Type',
                            },
                        },
                    },
                });
            }
        }
    }
}


const dif_tickets_chart = document.getElementById('dif_tickets_chart');

if (dif_tickets_chart) {
    const event_id = document.getElementById('event-charts').getAttribute('data-id');
    sendAjaxRequest('get', '/api/events/' + event_id + '/charts?' + encodeForAjax({ type: 'tickets_chart' }), {}, dif_tickets_chart_handler);

    function dif_tickets_chart_handler() {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            const dif_tickets_chartChartData = response.moucho;

            if (dif_tickets_chartChartData) {
                const maxYValue = Math.max(...dif_tickets_chartChartData.datasets.flatMap(dataset => dataset.data));

                const adjustedMaxYValue = maxYValue + 10;

                new Chart(dif_tickets_chart, {
                    type: 'line',
                    data: {
                        labels: dif_tickets_chartChartData.labels,
                        datasets: dif_tickets_chartChartData.datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                type: 'category',
                                labels: dif_tickets_chartChartData.labels,
                            },
                            y: {
                                beginAtZero: true,
                                max: adjustedMaxYValue
                            }
                        }
                    }
                });
            }
        }
    }
}

const all_tickets_chart = document.getElementById('all_tickets_chart');

if (all_tickets_chart) {
    const event_id = document.getElementById('event-charts').getAttribute('data-id');
    sendAjaxRequest('get', '/api/events/' + event_id + '/charts?' + encodeForAjax({ type: 'all_tickets_chart' }), {}, all_tickets_chart_handler);

    function all_tickets_chart_handler() {
        if (this.status === 200) {
            const response = JSON.parse(this.responseText);
            const all_tickets_ChartData = response.moucho;

            if (all_tickets_ChartData) {
                const maxYValue = Math.max(...all_tickets_ChartData.datasets.flatMap(dataset => dataset.data));

                const adjustedMaxYValue = maxYValue + 10;

                new Chart(all_tickets_chart, {
                    type: 'line',
                    data: {
                        labels: all_tickets_ChartData.labels,
                        datasets: all_tickets_ChartData.datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                type: 'category',
                                labels: all_tickets_ChartData.labels,
                            },
                            y: {
                                beginAtZero: true,
                                max: adjustedMaxYValue
                            }
                        }
                    }
                });
            }
        }
    }
}




const perc_sold_tickets_charts = document.querySelector('.perc_sold_tickets_charts');

if (perc_sold_tickets_charts) {
    const canvases = perc_sold_tickets_charts.querySelectorAll('canvas');
    const event_id = document.getElementById('event-charts').getAttribute('data-id');

    canvases.forEach(canvas => {
        const canva_ticket_type_id = canvas.id;
        const canva = canvas;

        const requestData = { canva: canva, canvaId: canva_ticket_type_id };

        sendAjaxRequest('get', '/api/events/' + event_id + '/charts?' + encodeForAjax(requestData), {}, per_sold_ctx_handler);
    });
}

function per_sold_ctx_handler() {
    if (this.status === 200) {
        let response = JSON.parse(this.responseText);
        let per_sold_pieChartData = response.moucho;
        let canva_ticket_type_id = response.chart_id;
        let canvas = document.getElementById(canva_ticket_type_id);

        let chartOptions = {
            plugins: {
                title: {
                    display: true,
                    text: '',
                },
                legend: {
                    position: 'top',
                },
            },
        };

        if (per_sold_pieChartData.data.datasets[0].data[0] === 100) {
            chartOptions.plugins.title.text = 'SOLD OUT';
        } else {
            chartOptions.plugins.title.text = `Distribution of Tickets for ${Math.round(per_sold_pieChartData.data.datasets[0].data[0])}% sold`;
        }

        new Chart(canvas, {
            type: 'pie',
            data: per_sold_pieChartData.data,
            options: {
                responsive: false,
                maintainAspectRatio: true,
                plugins: chartOptions.plugins,
            },
        });
    }
 }
