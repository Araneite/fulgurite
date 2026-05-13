// resources/js/animations/password.js

export default function registerPasswordEye(Alpine) {
    Alpine.data('passwordEye', () => ({
        visible: false,
        pupilX: 0,
        pupilY: 0,

        toggle() {
            this.visible = !this.visible;
        },

        trackPoint(mouseX, mouseY) {
            if (this.visible) {
                return;
            }

            const eye = this.$el.querySelector('.password-eye');

            if (!eye) {
                return;
            }

            const rect = eye.getBoundingClientRect();
            const centerX = rect.left + rect.width / 2;
            const centerY = rect.top + rect.height / 2;

            const dx = mouseX - centerX;
            const dy = mouseY - centerY;

            this.pupilX = Math.max(-4, Math.min(4, dx / 18));
            this.pupilY = Math.max(-3, Math.min(3, dy / 18));
        },

        reset() {
            this.pupilX = 0;
            this.pupilY = 0;
        },
    }));
}
