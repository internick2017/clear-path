<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    canLogin: {
        type: Boolean,
    },
    canRegister: {
        type: Boolean,
    },
});
</script>

<template>
    <Head title="Welcome to Clear Path" />
    <div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">
        <!-- Navigation -->
        <nav class="relative z-10">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-6">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <span class="ml-3 text-2xl font-bold text-gray-800">Clear Path</span>
                    </div>

                    <!-- Auth Links -->
                    <div v-if="canLogin" class="flex items-center space-x-4">
                        <Link
                            v-if="$page.props.auth?.user"
                            :href="route('dashboard')"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                        >
                            Dashboard
                        </Link>
                        <template v-else>
                            <Link
                                :href="route('login')"
                                class="px-4 py-2 text-gray-700 hover:text-blue-600 font-medium transition"
                            >
                                Iniciar Sesión
                            </Link>
                            <Link
                                v-if="canRegister"
                                :href="route('register')"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"
                            >
                                Registrarse
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="relative py-20 px-4 sm:px-6 lg:px-8">
            <div class="max-w-7xl mx-auto">
                <div class="text-center">
                    <h1 class="text-4xl sm:text-5xl md:text-6xl font-bold text-gray-900 mb-6">
                        Toma el Control de tu
                        <span class="text-blue-600">Futuro Financiero</span>
                    </h1>
                    <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">
                        Clear Path te ayuda a gestionar tus finanzas personales de manera inteligente. 
                        Controla gastos, establece presupuestos, alcanza tus metas y elimina deudas.
                    </p>
                    <div class="flex justify-center space-x-4">
                        <Link
                            v-if="canRegister"
                            :href="route('register')"
                            class="px-8 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition transform hover:scale-105"
                        >
                            Comienza Gratis
                        </Link>
                        <Link
                            :href="route('login')"
                            class="px-8 py-3 bg-white text-blue-600 font-semibold rounded-lg border-2 border-blue-600 hover:bg-blue-50 transition"
                        >
                            Ya tengo cuenta
                        </Link>
                    </div>
                </div>

                <!-- Hero Image/Illustration -->
                <div class="mt-16 relative">
                    <div class="bg-white rounded-2xl shadow-2xl p-8">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <!-- Stats Cards -->
                            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl p-6 text-white">
                                <div class="text-3xl font-bold mb-2">$12,450</div>
                                <div class="text-blue-100">Ingresos del Mes</div>
                            </div>
                            <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl p-6 text-white">
                                <div class="text-3xl font-bold mb-2">85%</div>
                                <div class="text-green-100">Meta de Ahorro</div>
                            </div>
                            <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl p-6 text-white">
                                <div class="text-3xl font-bold mb-2">$8,320</div>
                                <div class="text-purple-100">Gastos Controlados</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="py-20 bg-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="text-center mb-16">
                    <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                        Todo lo que Necesitas para tu Salud Financiera
                    </h2>
                    <p class="text-xl text-gray-600">
                        Herramientas poderosas y fáciles de usar para gestionar tu dinero
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                    <!-- Feature 1 -->
                    <div class="text-center p-6">
                        <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Control de Gastos</h3>
                        <p class="text-gray-600">
                            Registra y categoriza todos tus gastos. Identifica patrones y oportunidades de ahorro.
                        </p>
                    </div>

                    <!-- Feature 2 -->
                    <div class="text-center p-6">
                        <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Presupuestos Inteligentes</h3>
                        <p class="text-gray-600">
                            Crea presupuestos personalizados y recibe alertas cuando te acerques a tus límites.
                        </p>
                    </div>

                    <!-- Feature 3 -->
                    <div class="text-center p-6">
                        <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Metas Financieras</h3>
                        <p class="text-gray-600">
                            Establece objetivos de ahorro y visualiza tu progreso hacia la libertad financiera.
                        </p>
                    </div>

                    <!-- Feature 4 -->
                    <div class="text-center p-6">
                        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Gestión de Deudas</h3>
                        <p class="text-gray-600">
                            Organiza tus deudas y crea un plan estratégico para eliminarlas más rápido.
                        </p>
                    </div>

                    <!-- Feature 5 -->
                    <div class="text-center p-6">
                        <div class="w-16 h-16 bg-yellow-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Recordatorios</h3>
                        <p class="text-gray-600">
                            Nunca olvides un pago importante con notificaciones automáticas personalizadas.
                        </p>
                    </div>

                    <!-- Feature 6 -->
                    <div class="text-center p-6">
                        <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-900 mb-2">Análisis Visual</h3>
                        <p class="text-gray-600">
                            Visualiza tus finanzas con gráficos intuitivos y reportes detallados.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="py-20 bg-gradient-to-r from-blue-600 to-indigo-600">
            <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
                <h2 class="text-3xl sm:text-4xl font-bold text-white mb-6">
                    Comienza tu Camino hacia la Libertad Financiera
                </h2>
                <p class="text-xl text-blue-100 mb-8">
                    Únete a miles de usuarios que ya están mejorando su salud financiera con Clear Path
                </p>
                <Link
                    v-if="canRegister"
                    :href="route('register')"
                    class="inline-block px-8 py-4 bg-white text-blue-600 font-bold rounded-lg hover:bg-gray-100 transition transform hover:scale-105 shadow-lg"
                >
                    Crear Cuenta Gratuita
                </Link>
            </div>
        </section>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <div class="flex items-center mb-4 md:mb-0">
                        <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <span class="ml-2 text-xl font-bold">Clear Path</span>
                    </div>
                    <div class="text-gray-400 text-sm">
                        © 2025 Clear Path Financial. Todos los derechos reservados.
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>