import QuantitySelectorPlugin from "src/plugin/quantity-selector/quantity-selector.plugin";

export default class OwyCustomQuantitySelector extends QuantitySelectorPlugin {
    /**
     * call stepUp on element
     *
     * @private
     */
    _stepUp() {
        console.log(`step up`);
        const before = this._input.value;
        if(before * 1 === 1){
            this._btnMinus.removeAttribute(`disabled`)
        }
        this._input.stepUp();
        if (this._input.value !== before) {
            this._triggerChange();
        }
    }

    /**
     * call stepDown on element
     *
     * @private
     */
    _stepDown() {
        console.log(`step down`);
        const before = this._input.value;
        if(before * 1 === 1){
            this._btnMinus.setAttribute(`disabled`, `disabled`)
            return;
        }
        this._input.stepDown();
        const after = this._input.value;
        if (after !== before) {
            this._triggerChange();
        }
        if(after * 1 === 1){
            this._btnMinus.setAttribute(`disabled`, `disabled`)
        }
    }
}