document.addEventListener('DOMContentLoaded', function () {

    /*
    |--------------------------------------------------------------------------
    | Check Chart.js
    |--------------------------------------------------------------------------
    */

    if (typeof Chart === 'undefined') {

        console.error(
            'Chart.js is not loaded.'
        );

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Get Chart Data
    |--------------------------------------------------------------------------
    |
    | Dashboard does not define window.analyticsData.
    |
    | Analytics.php does define it.
    |
    | Therefore:
    |
    | - Analytics page uses existing server-side data.
    | - Dashboard fetches the protected API.
    |
    */

    if (
        typeof window.analyticsData === 'undefined'
        || typeof window.analyticsData !== 'object'
    ) {

        fetch(
            '../api/get_chart_data.php',
            {
                method: 'GET',

                credentials: 'same-origin',

                headers: {
                    'Accept': 'application/json'
                },

                cache: 'no-store'
            }
        )

        .then(function (response) {

            if (!response.ok) {

                throw new Error(
                    'Chart API returned HTTP ' +
                    response.status
                );
            }

            return response.json();
        })

        .then(function (data) {

            if (
                !data
                || data.success !== true
            ) {

                throw new Error(
                    data &&
                    data.message
                        ? data.message
                        : 'Invalid chart data.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | Convert API Format
            |--------------------------------------------------------------------------
            */

            window.analyticsData = {

                categories:
                    Array.isArray(
                        data.category?.labels
                    )
                    ? data.category.labels.map(
                        function (label, index) {

                            return {

                                category_name:
                                    String(label),

                                total:
                                    Number(
                                        data.category.data[index]
                                    ) || 0

                            };

                        }
                    )
                    : [],


                monthly:
                    Array.isArray(
                        data.monthly?.labels
                    )
                    ? data.monthly.labels.map(
                        function (label, index) {

                            return {

                                month:
                                    String(label),

                                total:
                                    Number(
                                        data.monthly.data[index]
                                    ) || 0

                            };

                        }
                    )
                    : []

            };


            renderCharts();

        })

        .catch(function (error) {

            console.error(
                'Unable to load dashboard chart data:',
                error
            );

        });

        return;
    }


    /*
    |--------------------------------------------------------------------------
    | Render Existing Analytics Data
    |--------------------------------------------------------------------------
    */

    renderCharts();


    /*
    |--------------------------------------------------------------------------
    | Render Charts
    |--------------------------------------------------------------------------
    */

    function renderCharts() {


        /*
        |--------------------------------------------------------------------------
        | Category Chart
        |--------------------------------------------------------------------------
        */

        const categoryCanvas =
            document.getElementById(
                'categoryChart'
            );


        if (
            categoryCanvas
            && Array.isArray(
                window.analyticsData.categories
            )
        ) {

            const categoryData =
                window.analyticsData.categories;


            const categoryLabels =
                categoryData.map(
                    function (item) {

                        return String(
                            item.category_name
                            ?? 'Uncategorized'
                        );

                    }
                );


            const categoryValues =
                categoryData.map(
                    function (item) {

                        return Number(
                            item.total
                        ) || 0;

                    }
                );


            /*
            |--------------------------------------------------------------------------
            | Destroy Existing Chart
            |--------------------------------------------------------------------------
            */

            const existingCategoryChart =
                Chart.getChart(
                    categoryCanvas
                );


            if (existingCategoryChart) {

                existingCategoryChart.destroy();

            }


            /*
            |--------------------------------------------------------------------------
            | Create Category Chart
            |--------------------------------------------------------------------------
            */

            if (
                categoryValues.length > 0
            ) {

                new Chart(
                    categoryCanvas,
                    {

                        type: 'doughnut',

                        data: {

                            labels:
                                categoryLabels,

                            datasets: [

                                {

                                    label:
                                        'Spending',

                                    data:
                                        categoryValues,

                                    borderWidth:
                                        1

                                }

                            ]

                        },

                        options: {

                            responsive:
                                true,

                            maintainAspectRatio:
                                false,

                            plugins: {

                                legend: {

                                    position:
                                        'bottom'

                                },

                                tooltip: {

                                    callbacks: {

                                        label:
                                            function (
                                                context
                                            ) {

                                                const label =
                                                    context.label
                                                    || 'Uncategorized';

                                                const value =
                                                    Number(
                                                        context.raw
                                                    )
                                                    || 0;

                                                return (
                                                    label
                                                    + ': ₹'
                                                    + value.toLocaleString(
                                                        'en-IN',
                                                        {
                                                            minimumFractionDigits: 2,
                                                            maximumFractionDigits: 2
                                                        }
                                                    )
                                                );

                                            }

                                    }

                                }

                            }

                        }

                    }
                );

            }

        }


        /*
        |--------------------------------------------------------------------------
        | Monthly Chart
        |--------------------------------------------------------------------------
        */

        const monthlyCanvas =
            document.getElementById(
                'monthlyChart'
            );


        if (
            monthlyCanvas
            && Array.isArray(
                window.analyticsData.monthly
            )
        ) {

            const monthlyData =
                window.analyticsData.monthly;


            const monthlyLabels =
                monthlyData.map(
                    function (item) {

                        return formatMonth(
                            item.month
                        );

                    }
                );


            const monthlyValues =
                monthlyData.map(
                    function (item) {

                        return Number(
                            item.total
                        ) || 0;

                    }
                );


            /*
            |--------------------------------------------------------------------------
            | Destroy Existing Chart
            |--------------------------------------------------------------------------
            */

            const existingMonthlyChart =
                Chart.getChart(
                    monthlyCanvas
                );


            if (existingMonthlyChart) {

                existingMonthlyChart.destroy();

            }


            /*
            |--------------------------------------------------------------------------
            | Create Monthly Chart
            |--------------------------------------------------------------------------
            */

            if (
                monthlyValues.length > 0
            ) {

                new Chart(
                    monthlyCanvas,
                    {

                        type: 'line',

                        data: {

                            labels:
                                monthlyLabels,

                            datasets: [

                                {

                                    label:
                                        'Monthly Spending',

                                    data:
                                        monthlyValues,

                                    borderWidth:
                                        2,

                                    tension:
                                        0.3,

                                    fill:
                                        false

                                }

                            ]

                        },

                        options: {

                            responsive:
                                true,

                            maintainAspectRatio:
                                false,

                            scales: {

                                y: {

                                    beginAtZero:
                                        true,

                                    ticks: {

                                        callback:
                                            function (
                                                value
                                            ) {

                                                return (
                                                    '₹'
                                                    +
                                                    Number(
                                                        value
                                                    ).toLocaleString(
                                                        'en-IN'
                                                    )
                                                );

                                            }

                                    }

                                }

                            },

                            plugins: {

                                tooltip: {

                                    callbacks: {

                                        label:
                                            function (
                                                context
                                            ) {

                                                const value =
                                                    Number(
                                                        context.raw
                                                    )
                                                    || 0;

                                                return (
                                                    '₹'
                                                    +
                                                    value.toLocaleString(
                                                        'en-IN',
                                                        {
                                                            minimumFractionDigits: 2,
                                                            maximumFractionDigits: 2
                                                        }
                                                    )
                                                );

                                            }

                                    }

                                }

                            }

                        }

                    }
                );

            }

        }

    }


    /*
    |--------------------------------------------------------------------------
    | Format Month
    |--------------------------------------------------------------------------
    */

    function formatMonth(month) {

        if (
            typeof month !== 'string'
            || !/^\d{4}-\d{2}$/.test(month)
        ) {

            return month;

        }


        const parts =
            month.split('-');


        const year =
            Number(parts[0]);


        const monthNumber =
            Number(parts[1]);


        if (
            monthNumber < 1
            || monthNumber > 12
        ) {

            return month;

        }


        const date =
            new Date(
                year,
                monthNumber - 1,
                1
            );


        return date.toLocaleDateString(
            'en-IN',
            {
                month: 'short',
                year: 'numeric'
            }
        );

    }

});