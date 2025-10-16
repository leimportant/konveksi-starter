<!DOCTYPE html>
<html>
<head>
    <title>Payroll Summary</title>
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
    </style>
</head>
<body>
    <h1>Payroll Summary</h1>
    <table>
        <thead>
            <tr>
                <th>Employee</th>
                <th>Total Gaji</th>
                <th>Total Uang Makan</th>
                <th>Total Lembur</th>
                <th>Total Potongan</th>
                <th>Total Net Gaji</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary as $row)
                <tr>
                    <td>{{ $row->employee->name }}</td>
                    <td>{{ number_format($row->total_gaji, 2) }}</td>
                    <td>{{ number_format($row->total_uang_makan, 2) }}</td>
                    <td>{{ number_format($row->total_lembur, 2) }}</td>
                    <td>{{ number_format($row->total_potongan, 2) }}</td>
                    <td>{{ number_format($row->total_net_gaji, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
