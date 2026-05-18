<script setup>
defineProps({
    icon: { type: String, required: true },
    class: { type: String, default: '' },
});
</script>

<template>
    <i class="fa-solid" :class="[`fa-${icon}`, $props.class]" aria-hidden="true" />
</template>
