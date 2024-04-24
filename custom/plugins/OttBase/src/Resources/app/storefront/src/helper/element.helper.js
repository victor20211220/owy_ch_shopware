export default class ElementHelper {
    static getDocumentCoordinates(el) {
        const box = el.getBoundingClientRect();
        const docEl = document.documentElement;
        const body = document.body;

        const scrollTop = docEl.scrollTop || body.scrollTop;
        const scrollLeft = docEl.scrollLeft || body.scrollLeft;

        const clientTop = docEl.clientTop || body.clientTop || 0;
        const clientLeft = docEl.clientLeft || body.clientLeft || 0;

        const top = box.top + scrollTop - clientTop;
        const left = box.left + scrollLeft - clientLeft;

        return { top, left };
    }

    // Relative to parent with position property (except static)
    static getPositionRelativeToParent(el) {
        return { top: el.offsetTop, left: el.offsetLeft };
    }

    static getHeight(el, includePadding = true, includeBorder = false, includeMargin = false) {
        const computedStyle = getComputedStyle(el);
        let height = el.clientHeight;
        const padding = parseInt(computedStyle.paddingTop.replace('px', ''))
            + parseInt(computedStyle.paddingBottom.replace('px', ''));

        if (!includePadding) {
            height -= padding;
        }

        if (includeBorder) {
            const borderBottom = parseInt(computedStyle.borderBottom.split(' ')[0].replace('px', ''));
            const borderTop = parseInt(computedStyle.borderTop.split(' ')[0].replace('px', ''));

            height += borderBottom + borderTop;
        }

        if (includeMargin) {
            height += parseInt(computedStyle.marginTop.replace('px', ''))
                + parseInt(computedStyle.marginBottom.replace('px', ''));
        }

        return height;
    }

    static getWidth(el, includePadding = true, includeBorder = false, includeMargin = false) {
        const computedStyle = getComputedStyle(el);
        let width = el.clientWidth;
        const padding = parseInt(computedStyle.paddingLeft.replace('px', ''))
            + parseInt(computedStyle.paddingRight.replace('px', ''));

        if (!includePadding) {
            width -= padding;
        }

        if (includeBorder) {
            const borderLeft = parseInt(computedStyle.borderLeft.split(' ')[0].replace('px', ''));
            const borderRight = parseInt(computedStyle.borderRight.split(' ')[0].replace('px', ''));

            width += borderLeft + borderRight;
        }

        if (includeMargin) {
            width += parseInt(computedStyle.marginLeft.replace('px', ''))
                + parseInt(computedStyle.marginRight.replace('px', ''));
        }

        return width;
    }

    static getPaddingTop(el) {
        const computedStyle = getComputedStyle(el);

        return parseInt(computedStyle.paddingTop.replace('px', ''));
    }
}
