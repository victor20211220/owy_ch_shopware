import Plugin from 'src/plugin-system/plugin.class';

export default class OttHistoryJumpPlugin extends Plugin {
    static options = {
        direction: 'backward',
    };

    init() {
        this.registerEvents();
    }

    registerEvents() {
        this.el.addEventListener('click', this.onClick.bind(this));
    }

    onClick(e) {
        e.preventDefault();

        this.jump();
    }

    jump(direction = this.options.direction) {
        if ('forward' === direction) {
            window.history.forward();
        } else {
            window.history.back();
        }
    }
}
