import LaravelEcho from 'laravel-echo';
import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';
import { onMounted, onUnmounted } from 'vue';

const CHANNEL = 'restaurante-orders';
const EVENT = '.OrderCreated';

/** Campanita breve con Web Audio (sin archivo externo). */
export function playReceptionBell() {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) {
            return;
        }
        const ctx = new AudioCtx();
        const playTone = (frequency, start, duration) => {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';
            osc.frequency.value = frequency;
            gain.gain.setValueAtTime(0.12, start);
            gain.gain.exponentialRampToValueAtTime(0.001, start + duration);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(start);
            osc.stop(start + duration);
        };
        const t = ctx.currentTime;
        playTone(880, t, 0.12);
        playTone(1174.66, t + 0.14, 0.18);
        window.setTimeout(() => ctx.close(), 500);
    } catch {
        // Navegador sin soporte o sin gesto de usuario: omitir sonido.
    }
}

export function notifyNewKitchenOrder(order) {
    const room = order?.room_number ?? order?.habitacion?.numero ?? '?';
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: `¡Nuevo pedido recibido de la habitación ${room}!`,
        showConfirmButton: false,
        timer: 4500,
        timerProgressBar: true,
    });
    playReceptionBell();
}

/**
 * Escucha pedidos de comida en tiempo real (canal público restaurante-orders).
 * @param {(order: object) => void} onNewOrder
 */
export function useRestauranteOrdersChannel(onNewOrder) {
    onMounted(() => {
        const client = window.Echo;
        if (!client || !(client instanceof LaravelEcho)) {
            return;
        }

        client.channel(CHANNEL).listen(EVENT, (payload) => {
            const incoming = payload?.order;
            if (!incoming?.id || incoming.service_type !== 'comida') {
                return;
            }
            onNewOrder(incoming);
            notifyNewKitchenOrder(incoming);
        });
    });

    onUnmounted(() => {
        window.Echo?.leave(CHANNEL);
    });
}
