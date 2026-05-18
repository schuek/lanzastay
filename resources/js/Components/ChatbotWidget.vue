<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import axios from 'axios';
import { MessageCircle, Send, X } from 'lucide-vue-next';

const props = defineProps({
    roomNumber: { type: String, default: '' },
    roomAccessToken: { type: String, default: '' },
});

const { t, locale } = useI18n();

const abierto = ref(false);
const mensajeNuevo = ref('');
const mensajes = ref([]);
const escribiendo = ref(false);
const contenedorMensajes = ref(null);
const widgetRef = ref(null);

/** Textos de UI reactivos al locale (vue-i18n) */
const chatTexts = computed(() => ({
    saludo: t('chat.saludo'),
    titulo: t('chat.titulo'),
    escribiendo: t('chat.escribiendo'),
    placeholder: t('chat.placeholder'),
    abrir: t('chat.abrir'),
    cerrar: t('chat.cerrar'),
    enviar: t('chat.enviar'),
}));

const pos = ref({ x: 0, y: 0 });
const dragging = ref(false);
const dragOffset = ref({ x: 0, y: 0 });
const pointerStart = ref({ x: 0, y: 0 });
const shouldToggleOnClick = ref(false);

const CLICK_THRESHOLD = 5;

function getWidgetSize() {
    const el = widgetRef.value;
    if (!el) {
        return { width: abierto.value ? 320 : 56, height: abierto.value ? 420 : 56 };
    }
    const rect = el.getBoundingClientRect();
    return { width: rect.width, height: rect.height };
}

function clampPosition(x, y) {
    const { width, height } = getWidgetSize();
    const maxX = Math.max(0, window.innerWidth - width);
    const maxY = Math.max(0, window.innerHeight - height);

    return {
        x: Math.min(Math.max(0, x), maxX),
        y: Math.min(Math.max(0, y), maxY),
    };
}

function setInitialPosition() {
    const { width, height } = getWidgetSize();
    pos.value = clampPosition(
        window.innerWidth - width - 24,
        window.innerHeight - height - 24,
    );
}

function repositionWithinBounds() {
    pos.value = clampPosition(pos.value.x, pos.value.y);
}

function getPointerCoords(event) {
    if (event.touches?.length) {
        return { x: event.touches[0].clientX, y: event.touches[0].clientY };
    }
    if (event.changedTouches?.length) {
        return { x: event.changedTouches[0].clientX, y: event.changedTouches[0].clientY };
    }
    return { x: event.clientX, y: event.clientY };
}

function isClickGesture(endX, endY) {
    const movimientoX = endX - pointerStart.value.x;
    const movimientoY = endY - pointerStart.value.y;
    return Math.abs(movimientoX) < CLICK_THRESHOLD && Math.abs(movimientoY) < CLICK_THRESHOLD;
}

function onPointerMove(event) {
    if (!dragging.value) {
        return;
    }

    const { x, y } = getPointerCoords(event);
    const movimientoX = Math.abs(x - pointerStart.value.x);
    const movimientoY = Math.abs(y - pointerStart.value.y);

    if (movimientoX >= CLICK_THRESHOLD || movimientoY >= CLICK_THRESHOLD) {
        pos.value = clampPosition(x - dragOffset.value.x, y - dragOffset.value.y);
        if (event.cancelable) {
            event.preventDefault();
        }
    }
}

function endDrag(event) {
    if (!dragging.value) {
        return;
    }

    const { x, y } = getPointerCoords(event);

    if (shouldToggleOnClick.value && isClickGesture(x, y)) {
        abierto.value = !abierto.value;
        nextTick(() => repositionWithinBounds());
    }

    dragging.value = false;
    shouldToggleOnClick.value = false;
    document.removeEventListener('mousemove', onPointerMove);
    document.removeEventListener('mouseup', endDrag);
    document.removeEventListener('touchmove', onPointerMove);
    document.removeEventListener('touchend', endDrag);
    document.removeEventListener('touchcancel', endDrag);
}

function startDrag(event, toggleOnClick = false) {
    if (event.target.closest('input, textarea, [data-chat-close], [data-chat-send]')) {
        return;
    }

    const { x, y } = getPointerCoords(event);
    dragging.value = true;
    shouldToggleOnClick.value = toggleOnClick;
    pointerStart.value = { x, y };
    dragOffset.value = { x: x - pos.value.x, y: y - pos.value.y };

    document.addEventListener('mousemove', onPointerMove);
    document.addEventListener('mouseup', endDrag);
    document.addEventListener('touchmove', onPointerMove, { passive: false });
    document.addEventListener('touchend', endDrag);
    document.addEventListener('touchcancel', endDrag);
}

function onResize() {
    repositionWithinBounds();
}

async function scrollAlFinal() {
    await nextTick();
    const el = contenedorMensajes.value;
    if (el) {
        el.scrollTop = el.scrollHeight;
    }
}

/** Mensajes del sistema usan `key` (reactivo); respuestas de API usan `text` fijo */
function createBotMessage(key) {
    return { role: 'bot', key };
}

function messageText(message) {
    if (message.key) {
        return t(message.key);
    }
    return message.text ?? '';
}

function isGreetingOnlyConversation() {
    return (
        mensajes.value.length === 1
        && mensajes.value[0]?.role === 'bot'
        && mensajes.value[0]?.key === 'chat.saludo'
    );
}

function resetGreeting() {
    mensajes.value = [createBotMessage('chat.saludo')];
}

async function enviarMensaje() {
    const texto = mensajeNuevo.value.trim();
    if (!texto) {
        return;
    }

    mensajes.value.push({ role: 'user', text: texto });
    mensajeNuevo.value = '';
    escribiendo.value = true;
    await scrollAlFinal();

    try {
        const payload = {
            message: texto,
            room_number: props.roomNumber || null,
            access_token: props.roomAccessToken || null,
        };
        const { data } = await axios.post('/chat', payload);
        const reply = typeof data?.reply === 'string' && data.reply !== '' ? data.reply : null;

        if (reply) {
            mensajes.value.push({ role: 'bot', text: reply });
        } else {
            mensajes.value.push(createBotMessage('chat.error_respuesta'));
        }
    } catch {
        mensajes.value.push(createBotMessage('chat.error_conexion'));
    } finally {
        escribiendo.value = false;
        await scrollAlFinal();
    }
}

watch(abierto, () => {
    nextTick(() => repositionWithinBounds());
});

watch(locale, () => {
    if (isGreetingOnlyConversation()) {
        resetGreeting();
    }
});

onMounted(() => {
    resetGreeting();
    nextTick(() => setInitialPosition());
    window.addEventListener('resize', onResize);
});

onUnmounted(() => {
    dragging.value = false;
    document.removeEventListener('mousemove', onPointerMove);
    document.removeEventListener('mouseup', endDrag);
    document.removeEventListener('touchmove', onPointerMove);
    document.removeEventListener('touchend', endDrag);
    document.removeEventListener('touchcancel', endDrag);
    window.removeEventListener('resize', onResize);
});
</script>

<template>
    <div
        ref="widgetRef"
        class="fixed z-[60] select-none"
        :style="{ left: `${pos.x}px`, top: `${pos.y}px` }"
    >
        <button
            v-if="!abierto"
            type="button"
            class="flex h-14 w-14 items-center justify-center rounded-full bg-[#2F2A26] text-white shadow-lg transition hover:bg-[#A64B35]"
            :class="dragging ? 'cursor-grabbing' : 'cursor-pointer'"
            :aria-label="chatTexts.abrir"
            @mousedown="startDrag($event, true)"
            @touchstart="startDrag($event, true)"
        >
            <MessageCircle class="h-6 w-6 pointer-events-none" />
        </button>

        <div
            v-else
            class="flex w-80 flex-col overflow-hidden rounded-2xl bg-white shadow-2xl sm:w-96"
            role="dialog"
            :aria-label="chatTexts.titulo"
        >
            <header
                class="flex shrink-0 cursor-grab items-center justify-between bg-[#2F2A26] px-4 py-3 text-white active:cursor-grabbing"
                :class="{ 'cursor-grabbing': dragging }"
                @mousedown="startDrag($event, false)"
                @touchstart="startDrag($event, false)"
            >
                <p class="pointer-events-none text-sm font-semibold">{{ chatTexts.titulo }}</p>
                <button
                    type="button"
                    class="cursor-pointer rounded-md p-1 text-white/90 hover:bg-white/10"
                    :aria-label="chatTexts.cerrar"
                    data-chat-close
                    @click="abierto = false"
                >
                    <X class="h-5 w-5" />
                </button>
            </header>

            <div ref="contenedorMensajes" class="h-80 overflow-y-auto p-4">
                <div class="flex flex-col gap-3">
                    <div
                        v-for="(m, i) in mensajes"
                        :key="m.key ? `i18n-${m.key}-${locale}` : `text-${i}-${m.text?.slice(0, 24)}`"
                        :class="m.role === 'user' ? 'ml-6 flex justify-end' : 'mr-6 flex justify-start'"
                    >
                        <p
                            :class="
                                m.role === 'user'
                                    ? 'max-w-[85%] rounded-2xl rounded-br-md bg-[#2F2A26] px-3 py-2 text-sm text-white'
                                    : 'max-w-[85%] rounded-2xl rounded-bl-md bg-[#F3F4F6] px-3 py-2 text-sm text-[#2F2A26]'
                            "
                        >
                            {{ messageText(m) }}
                        </p>
                    </div>
                    <div v-if="escribiendo" class="mr-6 flex justify-start">
                        <p class="rounded-2xl rounded-bl-md bg-[#F3F4F6] px-3 py-2 text-xs italic text-[#2F2A26]/70">
                            {{ chatTexts.escribiendo }}
                        </p>
                    </div>
                </div>
            </div>

            <form class="flex shrink-0 gap-2 border-t border-[#2F2A26]/10 p-3" @submit.prevent="enviarMensaje">
                <input
                    v-model="mensajeNuevo"
                    type="text"
                    autocomplete="off"
                    :placeholder="chatTexts.placeholder"
                    class="min-w-0 flex-1 rounded-xl border border-[#2F2A26]/15 px-3 py-2 text-sm text-[#2F2A26] placeholder:text-[#2F2A26]/40 focus:border-[#A64B35] focus:outline-none focus:ring-1 focus:ring-[#A64B35]"
                />
                <button
                    type="submit"
                    data-chat-send
                    class="inline-flex shrink-0 items-center justify-center rounded-xl bg-[#2F2A26] px-3 py-2 text-white transition hover:bg-[#A64B35]"
                    :aria-label="chatTexts.enviar"
                >
                    <Send class="h-4 w-4" />
                </button>
            </form>
        </div>
    </div>
</template>
