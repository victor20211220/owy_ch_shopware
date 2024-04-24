import flatpickr from 'flatpickr';
import Locales from 'flatpickr/dist/l10n/index';
import HttpClient from 'src/service/http-client.service';
import DateRange from '../date-range/date-range.plugin';

export default class RentalCalendar extends DateRange {

    static options = {
        rentDataUrl: null,
        productPrice: 0,
        productPrices: [],
        productPricesMode: null,
        mode: 1,
        offset: 0,
        minDuration: 1,
        maxDuration: 730,
        fixedPeriod: false,
        blockedWeekdays: [],
        notSelectableDates: [],
        removeBlockedDays: false,
        currentRentalPeriodStart: null,
        currentRentalPeriodEnd: null,
        language: 'en-GB',
        currency: 'EUR',
        flatpickrOptions: {
            mode: "range",
            dateFormat: "Y-m-dTH:i:S",
            altInput: true,
            altFormat: "d. F Y",
            enableTime: false,
            time_24hr: true,
            position: "below",
            inline: true,
            locale: {
                'firstDayOfWeek': 1
            },
            _notSelectable: []
        }
    };

    /**
     * Plugin initializer
     *
     * @returns {void}
     */
    init() {
        this.httpClient = new HttpClient();
        this._rentButton = document.getElementById('rent-button');
        this._rentQuantity = document.querySelector('.product-detail-quantity-input');
        this._createFormatter();
        this._getRentData();
        this._createCalendar();

        this._registerEvents();
    }

    /**
     * Register all needed events
     *
     * @private
     */
    _registerEvents() {
        // Update the price if the quantity is changed
        this._rentQuantity.addEventListener('change', this._updateRentalPrice.bind(this));
        // Get current rent periods after product was added to cart
        document.$emitter.subscribe('onCloseOffcanvas', this._reloadRentData.bind(this));
    }

    /**
     * Create the calendar and define the callback for the change event
     */
    _createCalendar() {
        this._flatpickr = flatpickr(this.el, {
            ...this.options.flatpickrOptions,
            ...this._getLocale(),
            minDate: new Date().fp_incr(this.options.offset), // earliest rent start
            onDayCreate: function (dObj, dStr, fp, dayElem) {
                let date = dayElem.dateObj;

                if (!this._dateIsSelectable(date)) {
                    dayElem.classList.add("notSelectable");
                }
            }.bind(this),
            onChange: function (selectedDates) {
                let that = this,
                    notSelectable = false;

                if (this.options.currentRentalPeriodStart && this.options.currentRentalPeriodEnd) {
                    selectedDates = this._getCurrentDates();

                    selectedDates.forEach(function (date) {
                        if (date < that._flatpickr.config._minDate) {
                            notSelectable = true;
                        }
                    });

                    if (!notSelectable) this._flatpickr.setDate(selectedDates, false);
                }

                if (this.options.fixedPeriod && selectedDates && selectedDates.length === 1) {
                    let newDates = [
                        selectedDates[0],
                        selectedDates[0].fp_incr(this.options.minDuration - 1)
                    ];

                    this._flatpickr.setDate(newDates, false);

                    selectedDates = newDates;
                }

                if (!this.options.currentRentalPeriodStart && !this.options.currentRentalPeriodEnd) {
                    selectedDates.forEach(function (date) {
                        if (!that._dateIsSelectable(date)) {
                            notSelectable = true;
                        }
                    });
                }

                if (notSelectable) {
                    that._flatpickr.setDate([], false)
                    return;
                }

                if (selectedDates && selectedDates.length > 1) {
                    let periodStart = this._toUTCDate(selectedDates[0]),
                        periodEnd = this._toUTCDate(selectedDates[1]),
                        minAvailable = parseInt(this._rentQuantity.getAttribute('max'));

                    // Get the rent duration from the selected dates
                    this._rentDuration = this._getDateDifference(periodStart, periodEnd);
                    // Change the end date if the minimum number of days is not reached
                    /*if (this._rentDuration < this.options.minDuration) {
                        this._flatpickr.setDate([
                            selectedDates[0],
                            selectedDates[0].fp_incr(this.options.minDuration - 1)
                        ], true)
                    }
                    // change the end date if the maximum number of days has been exceeded
                    if (this._rentDuration > this.options.maxDuration) {
                        this._flatpickr.setDate([
                            selectedDates[0],
                            selectedDates[0].fp_incr(this.options.maxDuration - 1)
                        ], true)
                    }*/
                    // Update the rental price according to the currently selected period
                    this._updateRentalPrice();
                    // Change the available stock to the lowest available stock in the selected period
                    if (this._rentsPerDay) {
                        let currentDate = periodStart,
                            currentDateString = this._dateToIsoString(currentDate);

                        for (let i = 0; i < this._rentDuration; i++) {
                            if (this._rentsPerDay[currentDateString] !== undefined) {
                                
                                if (this._rentsPerDay[currentDateString].minAvailable < minAvailable) {
                                    minAvailable = this._rentsPerDay[currentDateString].minAvailable;
                                }
                            }

                            currentDate.setDate(currentDate.getDate() + 1);
                            currentDateString = this._dateToIsoString(currentDate);
                        }
                    }
                    
                    // Disable all quantities that are no longer available
                    this._rentQuantity.setAttribute('max', minAvailable);

                    // If the previous selected quantity is no longer available, reduce it
                    if (this._rentQuantity.value > minAvailable) {
                        this._rentQuantity.value = minAvailable;
                    }
                    // Activate the rent button and quantity select if stock is at least 1
                    if (minAvailable > 0) {
                        this._rentButton.disabled = false;
                        this._rentQuantity.disabled = false;
                    }
                } else {
                    // Disable quantity selection and rent button if no or only one date is selected
                    this._rentButton.disabled = true;
                    this._rentQuantity.disabled = true;
                }
            }.bind(this)
        });

        this._setCurrentRentalPeriod();
    }

    /**
     * Update the rent data (rented and blocked days)
     */
    _reloadRentData() {
        this._getRentData();
        this._flatpickr.clear();
    }

    /**
     * Calculate and set the rental price
     */
    _updateRentalPrice() {
        if (parseInt(this._rentQuantity.value) > parseInt(this._rentQuantity.getAttribute('max'))) {
            this._rentQuantity.value = this._rentQuantity.getAttribute('max');
        }

        if(!this._rentDuration) return;

        let priceCalcQuantity = this._getPriceCalcQuantity(this._rentQuantity.value);

        this._setPrice(this._calculateRentalPrice(
            this._rentDuration,
            this._calculateUnitPrice(priceCalcQuantity),
            this._rentQuantity.value
        ));
    }

    /**
     * Create the formatter needed to format the rental price according to the current locale
     */
    _createFormatter() {
        this.currencyFormatter = new Intl.NumberFormat(this.options.language, {
            style: 'currency',
            currency: this.options.currency,
        });
    }

    /**
     * Set the price according to the current locale
     * 
     * @param {float} rentalPrice 
     */
    _setPrice(rentalPrice) {
        let priceElement = document.getElementById('rental-price');
        priceElement.innerHTML = this.currencyFormatter.format(rentalPrice);
    }

    /**
     * Get and set the rent times and the blocked periods for the product
     */
    _getRentData() {
        this.httpClient.get(this.options.rentDataUrl, (response) => {
            let responseData = JSON.parse(response);

            this._rentsPerDay = responseData['rentTimes'];
            this._setBlockedPeriods(responseData['blockedDays']);

            if (responseData['currentRentalPeriods']) {
                this.options.currentRentalPeriodStart = responseData['currentRentalPeriods']['startDate'];
                this.options.currentRentalPeriodEnd = responseData['currentRentalPeriods']['endDate'];

                this._setCurrentRentalPeriod();
            } else {
                this.options.currentRentalPeriodStart = null;
                this.options.currentRentalPeriodEnd = null;
            }
        });
    }

    /**
     * Disable all blocked days in the calendar
     * 
     * @param {array} blockedDays 
     */
    _setBlockedPeriods(blockedDays) {
        blockedDays.forEach((element, index) => {
            blockedDays[index] = new Date(element);
        });

        this._flatpickr.set("disable", blockedDays);
    }

    /**
     * Calculate the full rental price
     * 
     * @param {int} rentDuration 
     * @param {float} basePrice 
     * @param {int} quantity 
     * @returns {float}
     */
    _calculateRentalPrice(rentDuration, unitPrice, quantity) {
        return rentDuration * unitPrice * quantity;
    }

    /**
     * Get the current price for one unit
     * 
     * @param {int} quantity 
     * @returns {float}
     */
    _calculateUnitPrice(quantity) {
        // Return the default price if no graduated prices are set
        if (typeof this.options.productPrices[0] === 'undefined') {
            return this.options.productPrice;
        }
        // If only one graduated price is set, use it. 
        // Otherwise, get the correct price for the set quantity
        let graduatedPrices = this.options.productPrices,
            unitPrice = 0;

        if (graduatedPrices.length > 1) {
            for (var i = 0; i < graduatedPrices.length; i++) {
                unitPrice = graduatedPrices[i].unitPrice;

                if (parseInt(quantity) <= graduatedPrices[i].quantity) {
                    return unitPrice;
                }
            }
        } else if (graduatedPrices.length == 1) {
            unitPrice = graduatedPrices[0].unitPrice;
        }

        return unitPrice;
    }

    /**
     * If "Graduation by rental period" is active, the rent duration is used as the quantity for the graduated prices
     * 
     * @param {int} quantity 
     * @returns 
     */
    _getPriceCalcQuantity(quantity) {
        if (parseInt(this.options.productPricesMode) === 1) {
            if (isNaN(this._rentDuration)) {
                quantity = 1;
            } else {
                quantity = this._rentDuration;
            }
        }

        return quantity;
    }

    /**
     * Set the date as utc date
     * 
     * @param {Date} date 
     */
    _toUTCDate(date) {
        date.setUTCFullYear(date.getFullYear());
        date.setUTCMonth(date.getMonth());
        date.setUTCDate(date.getDate());
        date.setUTCHours(date.getHours());

        return date;
    }

    /**
     * Convert a date to an iso string without milliseconds and with timezone
     * 
     * @param {Date} date 
     * @returns 
     */
    _dateToIsoString(date) {
        // Convert date to iso string
        let isoString = date.toISOString();
        // Remove milliseconds and add timezone string
        isoString = isoString.substring(0, isoString.length - 5) + '+00:00';

        return isoString;
    }

    _getNotSelectableDatesInRange(firstDate, secondDate) {
        let currentDate = new Date(firstDate.getTime()),
            notSelectable = 0;

        while (this._dateWithoutTime(currentDate) != this._dateWithoutTime(secondDate)) {
            if (this.options.blockedWeekdays.includes(currentDate.getDay()) || this.options.notSelectableDates.includes(this._dateWithoutTime(currentDate))) {
                notSelectable++;
            }

            currentDate.setDate(currentDate.getDate() + 1);
        };

        return notSelectable;
    }

    _dateIsSelectable(date) {
        if ((this.options.currentRentalPeriodStart && this.options.currentRentalPeriodEnd) ||
            (this.options.blockedWeekdays && this.options.blockedWeekdays.length > 0 && this.options.blockedWeekdays.includes(date.getDay())) ||
            (this.options.notSelectableDates && this.options.notSelectableDates.length > 0 && this.options.notSelectableDates.includes(this._dateWithoutTime(date)))) {
            return false;
        }

        return true;
    }
}
