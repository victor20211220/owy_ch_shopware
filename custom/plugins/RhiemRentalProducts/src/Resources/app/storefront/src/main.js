import RentalCalendar from "./plugin/calendar/calendar.plugin";
import DateRange from "./plugin/date-range/date-range.plugin";

const PluginManager = window.PluginManager;

PluginManager.register('RentalCalendar', RentalCalendar, '[data-rental-calendar]');
PluginManager.register('DateRange', DateRange, '[data-date-range]');
