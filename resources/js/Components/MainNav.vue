<template>
  <nav class="bg-white shadow mb-6">
    <div class="max-w-7xl mx-auto px-4 py-3 flex gap-6 items-center">
      <Link href="/dashboard" class="font-semibold text-gray-700 hover:text-blue-600">Dashboard</Link>
      <Link href="/debts/plan" class="font-semibold text-gray-700 hover:text-blue-600">Debt Plan</Link>
      <Link href="/profile" class="font-semibold text-gray-700 hover:text-blue-600">Profile</Link>
      <Link href="/" class="font-semibold text-gray-700 hover:text-blue-600">Welcome</Link>
      <form method="POST" action="/logout" class="inline">
        <input type="hidden" name="_token" :value="csrf" />
        <button type="submit" class="ml-4 font-semibold text-red-600 hover:text-red-800">Logout</button>
      </form>
    </div>
  </nav>
</template>

<script setup>
import { usePage, Link } from '@inertiajs/vue3';
const csrf = usePage().props.csrf_token ?? '';
</script>
