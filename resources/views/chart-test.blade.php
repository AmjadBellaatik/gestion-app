<!DOCTYPE html>
<html
    lang="{{ app()->getLocale() }}"
    dir="{{ app()->getLocale() == 'ar' ? 'rtl' : 'ltr' }}"
>

<head>

    <meta charset="UTF-8">

    <title>
        {{ __('messages.dashboard') }}
    </title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

</head>

<body>

    <div style="width: 800px; margin: 50px auto;">

        <canvas id="salesChart"></canvas>

    </div>

    <script>

        const ctx =
            document.getElementById('salesChart');

        new Chart(ctx, {

            type: 'bar',

            data: {

                labels: [
                    'January',
                    'February',
                    'March',
                    'April'
                ],

                datasets: [{

                    label: 'Sales',

                    data: [
                        1200,
                        1900,
                        3000,
                        2500
                    ],

                    borderWidth: 1

                }]

            },

            options: {

                responsive: true,

                scales: {

                    y: {
                        beginAtZero: true
                    }

                }

            }

        });

    </script>

</body>
</html>