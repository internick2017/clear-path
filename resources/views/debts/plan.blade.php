<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Debt Payoff Plan') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="mb-4">Strategy: <span class="font-bold">{{ ucfirst($method) }}</span></h3>
                    <form method="GET" action="{{ route('debts.plan') }}" class="mb-6 flex items-center gap-4">
                        <label for="extra_payment" class="font-semibold">Extra Payment:</label>
                        <input type="number" name="extra_payment" id="extra_payment" value="{{ $extra_payment ?? 0 }}" min="0" step="1" class="border rounded px-2 py-1">
                        <input type="hidden" name="method" value="{{ $method }}">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Apply</button>
                    </form>
                    <table class="min-w-full bg-white border">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 border">Name</th>
                                <th class="px-4 py-2 border">Amount</th>
                                <th class="px-4 py-2 border">Interest Rate (%)</th>
                                <th class="px-4 py-2 border">Minimum Payment</th>
                                <th class="px-4 py-2 border">Due Date</th>
                                <th class="px-4 py-2 border">Estimated Months</th>
                                <th class="px-4 py-2 border">With Extra Payment</th>
                                <th class="px-4 py-2 border">Strategy</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($plan as $debt)
                                <tr>
                                    <td class="px-4 py-2 border">{{ $debt['name'] }}</td>
                                    <td class="px-4 py-2 border">${{ number_format($debt['amount'], 2) }}</td>
                                    <td class="px-4 py-2 border">{{ $debt['interest_rate'] }}</td>
                                    <td class="px-4 py-2 border">${{ number_format($debt['minimum_payment'], 2) }}</td>
                                    <td class="px-4 py-2 border">{{ $debt['due_date'] }}</td>
                                    <td class="px-4 py-2 border">{{ $debt['estimated_months'] == -1 ? 'Never' : $debt['estimated_months'] }}</td>
                                    <td class="px-4 py-2 border">{{ $debt['with_extra_payment'] == -1 ? 'Never' : $debt['with_extra_payment'] }}</td>
                                    <td class="px-4 py-2 border">{{ ucfirst($debt['strategy']) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-2 border text-center">No debts found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <!-- Chart.js Bar Chart -->
                    <div class="mt-10">
                        <h4 class="font-semibold mb-2">Comparación de meses para cada deuda</h4>
                        <canvas id="debtMonthsChart" height="120"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const debtNames = @json(collect($plan)->pluck('name'));
        const monthsNormal = @json(collect($plan)->pluck('estimated_months'));
        const monthsExtra = @json(collect($plan)->pluck('with_extra_payment'));
        const ctx = document.getElementById('debtMonthsChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: debtNames,
                datasets: [
                    {
                        label: 'Meses sin pago extra',
                        data: monthsNormal,
                        backgroundColor: 'rgba(59, 130, 246, 0.7)',
                    },
                    {
                        label: 'Meses con pago extra',
                        data: monthsExtra,
                        backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'top' },
                    title: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Meses'
                        }
                    }
                }
            }
        });
    </script>
</x-app-layout>
