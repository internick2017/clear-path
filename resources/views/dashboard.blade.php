<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    {{ __("You're logged in!") }}
                    <h3 class="mt-6 mb-2 text-lg font-bold">Budgets Overview</h3>
                    <table class="min-w-full bg-white border mb-8">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 border">Category</th>
                                <th class="px-4 py-2 border">Limit</th>
                                <th class="px-4 py-2 border">Spent</th>
                                <th class="px-4 py-2 border">Remaining</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach(auth()->user()->budgets as $budget)
                                <tr>
                                    <td class="px-4 py-2 border">{{ $budget->category }}</td>
                                    <td class="px-4 py-2 border">${{ number_format($budget->limit, 2) }}</td>
                                    <td class="px-4 py-2 border">${{ number_format($budget->spent, 2) }}</td>
                                    <td class="px-4 py-2 border">${{ number_format($budget->limit - $budget->spent, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>

                    <h3 class="mt-6 mb-2 text-lg font-bold">Income vs Expense (Current Month)</h3>
                    <canvas id="incomeExpenseChart" width="400" height="150"></canvas>
                    @php
                        $user = auth()->user();
                        $month = now()->format('Y-m');
                        $income = $user->transactions()->where('type', 'income')->whereRaw("DATE_FORMAT(date, '%Y-%m') = '$month'")->sum('amount');
                        $expense = $user->transactions()->where('type', 'expense')->whereRaw("DATE_FORMAT(date, '%Y-%m') = '$month'")->sum('amount');
                    @endphp
                    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                    <script>
                        const ctx = document.getElementById('incomeExpenseChart').getContext('2d');
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: ['Income', 'Expense'],
                                datasets: [{
                                    label: 'Amount',
                                    data: [{{ $income }}, {{ $expense }}],
                                    backgroundColor: [
                                        'rgba(54, 162, 235, 0.7)',
                                        'rgba(255, 99, 132, 0.7)'
                                    ],
                                    borderColor: [
                                        'rgba(54, 162, 235, 1)',
                                        'rgba(255, 99, 132, 1)'
                                    ],
                                    borderWidth: 1
                                }]
                            },
                            options: {
                                scales: {
                                    y: {
                                        beginAtZero: true
                                    }
                                }
                            }
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
