export default class FadeHelper {
    static fadeOut(el, duration = 400, force = true) {
        return new Promise(resolve => {
            el.style.opacity = 1;
            el.style.setProperty('transition-property', 'opacity');
            el.style.setProperty('transition-duration', `${duration / 1000}s`);

            setTimeout(() => {
                el.style.opacity = 0;

                setTimeout(() => {
                    force ? el.style.setProperty('display', 'none', 'important') : el.style.display = 'none';
                    resolve();
                }, duration);
            }, 25);
        });
    }

    static fadeIn(el, duration = 400, force = true) {
        return new Promise(resolve => {
            el.style.opacity = 0;
            force ? el.style.setProperty('display', 'block', 'important') : el.style.display = 'block';
            el.style.setProperty('transition-property', 'opacity');
            el.style.setProperty('transition-duration', `${duration / 1000}s`);

            setTimeout(() => {
                el.style.opacity = 1;

                setTimeout(() => resolve(), duration);
            }, 25);
        });
    }
}
