<script setup>
import Checkbox from '@/Components/Checkbox.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Iniciar Sesión - Clear Path" />
    
    <div class="min-h-screen flex">
        <!-- Left Panel - Branding -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-blue-600 to-indigo-700 p-12 flex-col justify-between">
            <div>
                <div class="flex items-center text-white mb-8">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                    </svg>
                    <span class="ml-3 text-3xl font-bold">Clear Path</span>
                </div>
                <h2 class="text-4xl font-bold text-white mb-6">
                    Bienvenido de Vuelta
                </h2>
                <p class="text-blue-100 text-lg mb-8">
                    Accede a tu panel de control financiero y continúa mejorando tu salud financiera.
                </p>
                
                <!-- Feature List -->
                <div class="space-y-4">
                    <div class="flex items-center text-white">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Control total de tus gastos</span>
                    </div>
                    <div class="flex items-center text-white">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Presupuestos personalizados</span>
                    </div>
                    <div class="flex items-center text-white">
                        <svg class="w-6 h-6 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <span>Seguimiento de metas financieras</span>
                    </div>
                </div>
            </div>
            
            <!-- Stats -->
            <div class="grid grid-cols-3 gap-4 mt-8">
                <div class="bg-white/10 backdrop-blur rounded-lg p-4">
                    <div class="text-2xl font-bold text-white">10K+</div>
                    <div class="text-blue-100 text-sm">Usuarios Activos</div>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-lg p-4">
                    <div class="text-2xl font-bold text-white">$2M+</div>
                    <div class="text-blue-100 text-sm">Ahorrados</div>
                </div>
                <div class="bg-white/10 backdrop-blur rounded-lg p-4">
                    <div class="text-2xl font-bold text-white">98%</div>
                    <div class="text-blue-100 text-sm">Satisfacción</div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="flex-1 flex items-center justify-center p-8 bg-gray-50">
            <div class="w-full max-w-md">
                <!-- Logo for Mobile -->
                <div class="lg:hidden text-center mb-8">
                    <Link href="/" class="inline-flex items-center justify-center">
                        <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                  d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                        <span class="ml-3 text-2xl font-bold text-gray-800">Clear Path</span>
                    </Link>
                </div>

                <!-- Form Header -->
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-2">Iniciar Sesión</h1>
                    <p class="text-gray-600">
                        ¿No tienes una cuenta? 
                        <Link :href="route('register')" class="text-blue-600 hover:text-blue-700 font-medium">
                            Regístrate gratis
                        </Link>
                    </p>
                </div>

                <!-- Status Message -->
                <div v-if="status" class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    {{ status }}
                </div>

                <!-- Login Form -->
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <InputLabel for="email" value="Correo Electrónico" class="text-gray-700 font-medium mb-2" />
                        <TextInput
                            id="email"
                            type="email"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            v-model="form.email"
                            required
                            autofocus
                            autocomplete="username"
                            placeholder="tu@email.com"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div>
                        <InputLabel for="password" value="Contraseña" class="text-gray-700 font-medium mb-2" />
                        <TextInput
                            id="password"
                            type="password"
                            class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            v-model="form.password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                        />
                        <InputError class="mt-2" :message="form.errors.password" />
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <Checkbox name="remember" v-model:checked="form.remember" class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500" />
                            <span class="ml-2 text-sm text-gray-600">Recordarme</span>
                        </label>

                        <Link
                            v-if="canResetPassword"
                            :href="route('password.request')"
                            class="text-sm text-blue-600 hover:text-blue-700 font-medium"
                        >
                            ¿Olvidaste tu contraseña?
                        </Link>
                    </div>

                    <button
                        type="submit"
                        class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="form.processing"
                    >
                        <span v-if="!form.processing">Iniciar Sesión</span>
                        <span v-else class="flex items-center justify-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Procesando...
                        </span>
                    </button>
                </form>

                <!-- Demo Account Info -->
                <div class="mt-8 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-600 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-800">Cuenta de Prueba</p>
                            <p class="text-sm text-blue-700 mt-1">
                                Email: <span class="font-mono">test@example.com</span><br>
                                Contraseña: <span class="font-mono">password</span>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Back to Home -->
                <div class="mt-6 text-center">
                    <Link href="/" class="text-sm text-gray-600 hover:text-gray-800">
                        ← Volver al inicio
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>