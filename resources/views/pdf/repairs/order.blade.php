<!DOCTYPE html>
<html>
<head>

    <meta charset="utf-8">

    <title>
        Repair Order
    </title>

    <style>

        body {

            font-family: DejaVu Sans;
            font-size: 12px;

        }

        table {

            width: 100%;
            border-collapse: collapse;

        }

        table,
        th,
        td {

            border: 1px solid #000;

        }

        th,
        td {

            padding: 8px;

        }

    </style>

</head>

<body>

    <h1>
        Repair Order
    </h1>

    <hr>

    <p>

        <strong>
            Ticket:
        </strong>

        {{ $repair->ticket_number }}

    </p>

    <p>

        <strong>
            Client:
        </strong>

        {{ $repair->client?->first_name }}

    </p>

    <p>

        <strong>
            Status:
        </strong>

        {{ $repair->status }}

    </p>

    <p>

        <strong>
            Date:
        </strong>

        {{ $repair->created_at }}

    </p>

    <hr>

    <h3>
        Repair Items
    </h3>

    <table>

        <thead>

            <tr>

                <th>
                    Product
                </th>

                <th>
                    Quantity
                </th>

                <th>
                    Price
                </th>

                <th>
                    Total
                </th>

            </tr>

        </thead>

        <tbody>

            @foreach(
                $repair->items as $item
            )

                <tr>

                    <td>
                        {{ $item->product?->name }}
                    </td>

                    <td>
                        {{ $item->quantity }}
                    </td>

                    <td>
                        {{ $item->price }}
                    </td>

                    <td>
                        {{ $item->total }}
                    </td>

                </tr>

            @endforeach

        </tbody>

    </table>

</body>
</html>