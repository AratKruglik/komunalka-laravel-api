<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>Показання лічильників</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #1a1a1a;
        }

        h1 {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 6px;
        }

        .period {
            font-size: 11px;
            color: #555;
            margin-bottom: 16px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead tr {
            background-color: #2d6a4f;
            color: #ffffff;
        }

        th {
            padding: 7px 8px;
            text-align: left;
            font-weight: bold;
            white-space: nowrap;
        }

        td {
            padding: 6px 8px;
            border-bottom: 1px solid #e0e0e0;
            vertical-align: top;
        }

        tbody tr:nth-child(even) {
            background-color: #f5f5f5;
        }

        .text-right {
            text-align: right;
        }

        .empty {
            text-align: center;
            padding: 24px;
            color: #888;
        }
    </style>
</head>
<body>
    <h1>Показання лічильників</h1>
    <p class="period">Період: {{ $fromDate }} — {{ $toDate }}</p>

    <table>
        <thead>
            <tr>
                <th>Дата</th>
                <th>Адреса</th>
                <th>Лічильник</th>
                <th>Тип послуги</th>
                <th class="text-right">Попереднє значення</th>
                <th class="text-right">Поточне значення</th>
                <th class="text-right">Споживання</th>
                <th>Примітки</th>
            </tr>
        </thead>
        <tbody>
            @forelse($readings as $reading)
                <tr>
                    <td>{{ $reading->reading_date?->format('d.m.Y') }}</td>
                    <td>
                        @if($reading->meter?->address)
                            {{ $reading->meter->address->city }},
                            {{ $reading->meter->address->street }}
                            {{ $reading->meter->address->building_number }}
                            @if($reading->meter->address->apartment_number)
                                кв. {{ $reading->meter->address->apartment_number }}
                            @endif
                        @endif
                    </td>
                    <td>{{ $reading->meter?->name ?? $reading->meter?->serial_number }}</td>
                    <td>{{ $reading->meter?->utilityType?->display_name }}</td>
                    <td class="text-right">{{ number_format((float) $reading->previous_reading_value, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $reading->reading_value, 2) }}</td>
                    <td class="text-right">{{ number_format((float) $reading->consumption, 2) }}</td>
                    <td>{{ $reading->notes }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">Показань за вказаний період не знайдено.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
