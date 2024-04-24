import flatpickr from 'flatpickr';
import Locales from 'flatpickr/dist/l10n/index';
import Plugin from 'src/plugin-system/plugin.class';

export default class DateRange extends Plugin {

    static options = {
        offset: 0,
        minDuration: 1,
        maxDuration: 730,
        fixedPeriod: false,
        blockedWeekdays: [],
        notSelectableDates: [],
        currentRentalPeriodStart: null,
        currentRentalPeriodEnd: null,
        language: 'en-GB',
        flatpickrOptions: {
            mode: "range",
            dateFormat: "Y-m-dTH:i:S",
            altInput: true,
            altFormat: "d. F Y",
            enableTime: false,
            time_24hr: true,
            position: "below",
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
        this._createCalendar();
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

                if ((this.options.blockedWeekdays && this.options.blockedWeekdays.includes(date.getDay())) ||
                    (this.options.notSelectableDates && this.options.notSelectableDates.includes(this._dateWithoutTime(date)))) {
                    dayElem.classList.add("notSelectable");
                }
            }.bind(this),
            onChange: function (selectedDates) {
                if (this.options.fixedPeriod && selectedDates && selectedDates.length === 1) {
                    let newDates = [
                        selectedDates[0],
                        selectedDates[0].fp_incr(this.options.minDuration - 1)
                    ];

                    this._flatpickr.setDate(newDates, false);
                }

                if (selectedDates && selectedDates.length > 1) {
                    let periodStart = this._toUTCDate(selectedDates[0]),
                        periodEnd = this._toUTCDate(selectedDates[1])

                    // Get the rent duration from the selected dates
                    this._rentDuration = this._getDateDifference(periodStart, periodEnd);

                    // Change the end date if the minimum number of days is not reached
                    if (this.options.minDuration && this._rentDuration < this.options.minDuration) {
                        this._flatpickr.setDate([
                            selectedDates[0],
                            selectedDates[0].fp_incr(this.options.minDuration - 1)
                        ], true)
                    }

                    // change the end date if the maximum number of days has been exceeded
                    if (this.options.maxDuration && this._rentDuration > this.options.maxDuration) {
                        this._flatpickr.setDate([
                            selectedDates[0],
                            selectedDates[0].fp_incr(this.options.maxDuration - 1)
                        ], true)
                    }
                }
            }.bind(this)
        });

        this._setCurrentRentalPeriod();
    }

    /**
     * Set the date as utc date
     * 
     * @param {Date} date 
     */
    _toUTCDate(date) {
        let newDate = new Date();

        newDate.setUTCFullYear(date.getFullYear());
        newDate.setUTCMonth(date.getMonth());
        newDate.setUTCDate(date.getDate());
        newDate.setUTCHours(date.getHours());

        return date;
    }

    /**
     * Get the difference of two dates in a specified unit
     * 
     * @param {Date} firstDate 
     * @param {Date} secondDate 
     * @param {string} unit 
     * @returns {int}
     */
    _getDateDifference(firstDate, secondDate, unit = 'day') {
        let millisecondsPerUnit;
        // Difference will be in milliseconds, so it needs to be divided to give the correct unit
        switch (unit) {
            case 'day':
                millisecondsPerUnit = 1000 * 60 * 60 * 24;
                break;
            case 'hour':
                millisecondsPerUnit = 1000 * 60 * 60;
                break;
            default:
                throw 'Unit not supported!';
        }
        // Dates are converted to utc to account for daylight saving time (DST)
        const firstUtc = Date.UTC(firstDate.getFullYear(), firstDate.getMonth(), firstDate.getDate());
        const secondUtc = Date.UTC(secondDate.getFullYear(), secondDate.getMonth(), secondDate.getDate());
        // Math.abs is used to always get the positive difference of the dates
        let dateDiff = Math.abs(secondUtc - firstUtc);
        // Convert to correct unit and always round up to get full units.
        // The last unit is missing in the comparison, so we have to add it to get the correct time span.
        let result = Math.ceil(dateDiff / millisecondsPerUnit) + 1;
        // Subtract all not selectable days from the period if configured
        if (this.options.removeBlockedDays) {
            result -= this._getNotSelectableDatesInRange(firstDate, secondDate);
        }
        // Return result
        return result;
    }

    /**
     * Set the language of the calendar with the current locale
     * Default is en-GB
     */
    _getLocale() {
        let localeIndex = 'default';

        if (this.options.language.substring(0, 2) !== 'en') {
            localeIndex = this.options.language.substring(0, 2);
        }

        return {
            locale: Locales[localeIndex],
        };
    }

    /**
     * Set the currently active start and end date in the calendar (if uniform rent periods are active)
     */
    _setCurrentRentalPeriod() {
        if (this.options.currentRentalPeriodStart && this.options.currentRentalPeriodEnd) {
            this._flatpickr.setDate(this._getCurrentDates(), true);
        }
    }

    /**
     * Get the currently active start and end date (if uniform rent periods are active)
     * 
     * @returns {array}
     */
    _getCurrentDates() {
        return [
            new Date(Date.parse(this.options.currentRentalPeriodStart)),
            new Date(Date.parse(this.options.currentRentalPeriodEnd))
        ];
    }

    /**
     * Get a date string without time
     * 
     * @param {Date} date 
     * @returns 
     */
    _dateWithoutTime(date) {
        // Return only the date parts as string
        return date.getFullYear() + '-' + ('0' + (date.getMonth() + 1)).slice(-2) + '-' + ('0' + date.getDate()).slice(-2);
    }
}
