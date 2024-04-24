import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

export default class OttBsHoverDropdownPlugin extends Plugin {
    static options = {
        dropdownOptions: {},
    };

    init() {
        this.timeout = null;
        this.initDropdown();
        this.registerEvents();
    }

    initDropdown() {
        this.el.setAttribute('data-bs-toggle', 'dropdown');
        // Store the dropdown menu associated with this element
        this.dropdownMenu = this.el.nextElementSibling;
        this.dropdown = new bootstrap.Dropdown(this.el, this.options.dropdownOptions);
    }

    registerEvents() {
        this.el.addEventListener('click', this.onTriggerClick.bind(this));
        this.el.parentElement.addEventListener('mouseenter', this.onMouseEnter.bind(this));
        this.el.parentElement.addEventListener('mouseleave', this.onMouseLeave.bind(this));
    }

    onMouseEnter() {
        clearTimeout(this.timeout);
        // Check if dropdownMenu is defined and not already shown
        if (this.dropdownMenu && !this.el.parentElement.classList.contains('show')) {
            this.dropdown.show(); // Use Bootstrap's show method to display the dropdown
        }

        this.el.parentElement.classList.add('show'); // Add 'show' to the parent element classList
    }

    onMouseLeave() {
        this.timeout = setTimeout(() => {
            // Hide the dropdown if mouse leaves and it's not being hovered over
            if (this.dropdownMenu && !this.dropdownMenu.matches(':hover')) {
                this.dropdown.hide(); // Use Bootstrap's hide method to hide the dropdown
                this.el.parentElement.classList.remove('show'); // Remove 'show' from the parent element classList
            }
        }, 500);
    }

    onTriggerClick(e) {
        e.preventDefault();
        this.el.parentElement.classList.remove('show');
        const href = DomAccess.getAttribute(this.el, 'href', false);

        if (href) {
            window.location = href;
        }
    }
}
