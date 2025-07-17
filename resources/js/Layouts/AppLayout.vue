
<template>
  <div class="min-h-screen bg-gray-100 font-sans antialiased">
    <nav class="bg-white border-b border-gray-100">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
          <div class="flex items-center gap-8">
            <a href="/dashboard" class="font-bold text-lg text-gray-800">Clear Path</a>
            <a href="/dashboard" class="text-gray-700 hover:text-blue-600">Dashboard</a>
            <a href="/goals" class="text-gray-700 hover:text-blue-600">Metas de ahorro</a>
            <a href="/goals/create" class="text-gray-700 hover:text-green-600">Crear meta</a>
            <a href="/debts/plan" class="text-gray-700 hover:text-blue-600">Deudas</a>
          </div>
          <div class="flex items-center gap-4">
            <span class="text-gray-600">{{ $page.props.auth?.user?.name }}</span>
            <form method="POST" :action="route('logout')">
              <button type="submit" class="text-gray-700 hover:text-red-600">Salir</button>
            </form>
          </div>
        </div>
      </div>
    </nav>
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <h1 class="text-2xl font-semibold text-gray-900">Clear Path - Debt Planner</h1>
      </div>
    </header>
    <main>
      <slot />
    </main>
    <footer class="bg-white shadow mt-6 py-4">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <p class="text-center text-sm text-gray-500">© 2025 Clear Path Financial</p>
      </div>
    </footer>
  </div>
</template>

<script setup>
// El helper route está disponible globalmente en Inertia
</script>

<style scoped>
nav a.router-link-exact-active {
  color: #2563eb;
  font-weight: bold;
}
</style>
