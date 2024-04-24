/* eslint-disable */
import Plugin from 'src/plugin-system/plugin.class';
import Tablesort from '@tablesort';

export default class OttTableSortablePlugin extends Plugin {
    static options = {
        extendDate: false,
        extendDotsep: false,
        extendFilesize: false,
        extendMonthname: false,
        extendNumber: false,
    };

    init() {
        this.extendTablesort();
        new Tablesort(this.el);
    }

    extendTablesort() {
        this.options.extendDate && this.extendDate();
        this.options.extendDotsep && this.extendDotsep();
        this.options.extendFilesize && this.extendFilesize();
        this.options.extendMonthname && this.extendMonthname();
        this.options.extendNumber && this.extendNumber();
    }

    extendDate() {
        // Basic dates in dd/mm/yy or dd-mm-yy format.
        // Years can be 4 digits. Days and Months can be 1 or 2 digits.
        (function(){
            const parseDate = function(date) {
                date = date.replace(/\-/g, '/');
                date = date.replace(/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/, '$3-$2-$1'); // format before getTime

                return new Date(date).getTime() || -1;
            };

            Tablesort.extend('date', function(item) {
                return (
                    -1 !== item.search(/(Mon|Tue|Wed|Thu|Fri|Sat|Sun)\.?\,?\s*/i)
                    || -1 !== item.search(/\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}/)
                    || -1 !== item.search(/(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)/i)
                ) && !isNaN(parseDate(item));
            }, function(a, b) {
                a = a.toLowerCase();
                b = b.toLowerCase();

                return parseDate(b) - parseDate(a);
            });
        }());
    }

    extendDotsep() {
        // Dot separated values. E.g. IP addresses or version numbers.
        Tablesort.extend('dotsep', function(item) {
            return /^(\d+\.)+\d+$/.test(item);
        }, function(a, b) {
            a = a.split('.');
            b = b.split('.');

            for (let i = 0, len = a.length, ai, bi; i < len; i++) {
                ai = parseInt(a[i], 10);
                bi = parseInt(b[i], 10);

                if (ai === bi) continue;
                if (ai > bi) return -1;
                if (ai < bi) return 1;
            }

            return 0;
        });
    }

    extendFilesize() {
        // Filesizes. e.g. '5.35 K', '10 MB', '12.45 GB', or '4.67 TiB'
        const compareNumber = function(a, b) {
            a = parseFloat(a);
            b = parseFloat(b);

            a = isNaN(a) ? 0 : a;
            b = isNaN(b) ? 0 : b;

            return a - b;
        };

        const cleanNumber = function(i) {
            return i.replace(/[^\-?0-9.]/g, '');
        };

        // Returns suffix multiplier
        // Ex. suffix2num('KB') -> 1000
        // Ex. suffix2num('KiB') -> 1024
        const suffix2num = function(suffix) {
            suffix = suffix.toLowerCase();
            const base = 'i' === suffix[1] ? 1024 : 1000;

            switch(suffix[0]) {
                case 'k':
                    return Math.pow(base, 2);
                case 'm':
                    return Math.pow(base, 3);
                case 'g':
                    return Math.pow(base, 4);
                case 't':
                    return Math.pow(base, 5);
                case 'p':
                    return Math.pow(base, 6);
                case 'e':
                    return Math.pow(base, 7);
                case 'z':
                    return Math.pow(base, 8);
                case 'y':
                    return Math.pow(base, 9);
                default:
                    return base;
            }
        };

        // Converts filesize to bytes
        // Ex. filesize2num('123 KB') -> 123000
        // Ex. filesize2num('123 KiB') -> 125952
        const filesize2num = function(filesize) {
            const matches = filesize.match(/^(\d+(\.\d+)?) ?((K|M|G|T|P|E|Z|Y|B$)i?B?)$/i);

            const num  = parseFloat(cleanNumber(matches[1]));
            const suffix = matches[3];

            return num * suffix2num(suffix);
        };

        Tablesort.extend('filesize', function(item) {
            return /^\d+(\.\d+)? ?(K|M|G|T|P|E|Z|Y|B$)i?B?$/i.test(item);
        }, function(a, b) {
            a = filesize2num(a);
            b = filesize2num(b);

            return compareNumber(b, a);
        });
    }

    extendMonthname() {
        Tablesort.extend('monthname', function(item) {
            return (
                -1 !== item.search(/(January|February|March|April|May|June|July|August|September|October|November|December)/i)
            );
        }, function(a, b) {
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

            return monthNames.indexOf(b) - monthNames.indexOf(a);
        });
    }

    extendNumber() {
        const cleanNumber = function(i) {
            return i.replace(/[^\-?0-9.]/g, '');
        };

        const compareNumber = function(a, b) {
            a = parseFloat(a);
            b = parseFloat(b);

            a = isNaN(a) ? 0 : a;
            b = isNaN(b) ? 0 : b;

            return a - b;
        };

        Tablesort.extend('number', function(item) {
            return item.match(/^[-+]?[£\x24Û¢´€]?\d+\s*([,\.]\d{0,2})/) // Prefixed currency
                || item.match(/^[-+]?\d+\s*([,\.]\d{0,2})?[£\x24Û¢´€]/) // Suffixed currency
                || item.match(/^[-+]?(\d)*-?([,\.]){0,1}-?(\d)+([E,e][\-+][\d]+)?%?$/); // Number
        }, function(a, b) {
            a = cleanNumber(a);
            b = cleanNumber(b);

            return compareNumber(b, a);
        });
    }
}
