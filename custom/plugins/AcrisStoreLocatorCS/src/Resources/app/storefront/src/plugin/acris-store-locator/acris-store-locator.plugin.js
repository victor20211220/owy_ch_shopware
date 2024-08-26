import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';
import DomAccess from 'src/helper/dom-access.helper';
import DeviceDetection from 'src/helper/device-detection.helper';
import CookieStorage from 'src/helper/storage/cookie-storage.helper';
import { COOKIE_CONFIGURATION_UPDATE } from 'src/plugin/cookie/cookie-configuration.plugin';
import { ACRIS_STORE_LOCATOR_COOKIE_CONFIGURATION_CHANGED } from '../cookie/cookie-configuration-override.plugin';

export default class AcrisStoreLocatorPlugin extends Plugin {

    static options = {
        apiKey: '',
        privacyMode: '',
        formSelector: '#search-stores',
        submitButton: '#submitButton',
        map: '#gmap_canvas',
        infoWindow: '#shop-infowindow',
        shopsResultList: '#shops-result-list',
        cookieIsNotSetMessageSelector: '.acris-store-locator-cookie-not-accepted',
        googleMapInnerSelector: '.acris-store-locator-map-container-inner',
        googleMapInitializationSelector: '#acris-store-locator-map-initialization',
        lat: '#lat',
        lng: '#lng',
        initSearch: '#initsearch',
        defaultLat: 48.323488,
        defaultLng: 14.361175,
        errorClass: 'has--error',
        customerUrl: '',
        mapCenterLocation: '',
        zoomFactor: 10,
        widthInfowindow: '',
        heightInfowindow: '',
        showCompanyName: '',
        showDepartment: '',
        showStreet: '',
        showZipcode: '',
        showCity: '',
        showCountry: '',
        showPhoneNumber: '',
        showMail: '',
        showURL: '',
        showOpening: '',
        storeIcon: '',
        homeIcon: '',
        groupId: '',
        storeDetailId: '',
        iconWidth: 30,
        iconHeight: 50,
        iconAnchorLeft: 15,
        iconAnchorRight: 50,
        storeInformationHeadline: 'h4',
        storeLocatorElement: '',
        storeLocatorLongitude: '',
        storeLocatorLatitude: '',
        storeLocatorBackUpAddressName: '',
        encryptedMail: false,
        mailClass: '.acris-store-locator-map-encrypted-email',
        mailTo: 'mailto:'
    };

    init() {
        this.isCookieIsSet(this);
        this._subscribeEvents();
    }

    /**
     *Method to initialize all the needed global vars
     */
    initVars() {
        this.google = window.google || {};
        this.maps = this.google.maps || {};

        if (this.options.zoomFactor < 0 || this.options.zoomFactor == null || this.options.zoomFactor === '') {
            this.options.zoomFactor = 10;
        }
        if (this.options.widthInfowindow < 0 || this.options.widthInfowindow == null || this.options.widthInfowindow === '') {
            this.options.widthInfowindow = 220;
        }
        if (this.options.heightInfowindow < 0 || this.options.heightInfowindow == null || this.options.heightInfowindow === '') {
            this.options.heightInfowindow = 20;
        }
        this.markers = [];
        this.bounds = new this.maps.LatLngBounds();
        this._client = new HttpClient(window.accessKey, window.contextToken);
        this.loadingMapOnce = true;
        this.mapLoadedTempVariable = false;
    }

    /**
     * Registers the necessary event listeners for the plugin
     */
    registerEvents() {
        if (this.form) this.form.addEventListener('submit', this.onTriggerRequest.bind(this));
    }

    _subscribeEvents() {
        document.$emitter.subscribe(COOKIE_CONFIGURATION_UPDATE, (updatedCookies) => {
            let cookieAccepted = false;

            if (typeof updatedCookies.detail['store-locator-cookie'] !== 'undefined') {
                cookieAccepted = updatedCookies.detail['store-locator-cookie'];
            }

            this.isCookieIsSet(this, true, cookieAccepted);
        });

        // document.$emitter.subscribe(ACRIS_STORE_LOCATOR_COOKIE_CONFIGURATION_CHANGED, (target) => {
        //     let cookieAccepted = false;
        //
        //     if (typeof target.detail !== 'undefined') {
        //         cookieAccepted = target.detail;
        //     }
        //
        //     this.isCookieIsSet(this, true, cookieAccepted);
        // });
    }

    /**
     * Event listener handler which will be called when the user clicks on the associated element.
     */
    onTriggerRequest(event) {
        event.preventDefault();
        this.mapRequest();
    }

    /**
     * Centers the map to the searched location and sends the form data to the controller
     */
    mapRequest() {
        if (this.form.elements['locator[place]'].value !== null) {
            const geocoder = new this.maps.Geocoder();
            this.geocodeAddress(geocoder, this.map, this.form.elements['locator[place]'].value, true);
        }
    }

    /**
     * Method to set the center of the google map map
     * @params geocoder, resultsMap, address
     */
    geocodeAddress(geocoder, resultsMap, address, sendData) {
        //to prevent invalid geocode request
        if (!address) {
            address = this.options.mapCenterLocation;
        }

        if (this.options.storeLocatorElement === '1') {
            address = this.options.storeLocatorBackUpAddressName;
        }

        geocoder.geocode({
            'address': address,
            'componentRestrictions': {
                'country': 'CH'
            }
        }, (results, status) => {
            if (status === 'OK') {
                if (this.options.storeLocatorElement === '1') {
                    const defaultCenter = { lat: this.options.storeLocatorLatitude, lng: this.options.storeLocatorLongitude };
                    resultsMap.setCenter(defaultCenter);
                    this.marker = new this.maps.Marker({
                        map: resultsMap
                    });
                    // only send data on form submition
                    if (sendData) {
                        this.sendDataToController(this.options.storeLocatorLatitude, this.options.storeLocatorLongitude);
                    }
                } else {
                    resultsMap.setCenter(results[0].geometry.location);
                    this.marker = new this.maps.Marker({
                        map: resultsMap
                    });
                    // only send data on form submition
                    if (sendData) {
                        this.sendDataToController(results[0].geometry.location.lat(), results[0].geometry.location.lng());
                    }
                }
            } else {
                const defaultCenter = { lat: this.options.defaultLat, lng: this.options.defaultLng };
                resultsMap.setCenter(defaultCenter);
                // only send data on form submition
                if (sendData) {
                    this.sendDataToController(this.options.defaultLat, this.options.defaultLng)
                }
            }
        }).catch();
    }

    /**
     * Method to send the data to the controller
     */
    sendDataToController(lat, lng) {
        let city = this.form.elements['locator[place]'].value + ', Schweiz';
        let distance = this.form.elements['locator[distance]'].value;
        if(this.mapLoadedTempVariable === false){
            city = "";
            distance = "0";
            this.mapLoadedTempVariable = true;
        }
        this._client.post(this.options.customerUrl, JSON.stringify({
            'city': city,
            'distance': distance,
            'lat': lat,
            'lng': lng,
            'handlerpoints': this.form.elements['handlerpoints'].value

        }), this._onLoaded.bind(this));
    }

    isCookieIsSet(value, updated = null, accepted = false) {
        let cookieIsNotSetMessage = DomAccess.querySelector(value.el, this.options.cookieIsNotSetMessageSelector, false);
        let googleMapInner = DomAccess.querySelector(value.el, this.options.googleMapInnerSelector, false);
        if (!cookieIsNotSetMessage || !googleMapInner) return;

        if (this.options.privacyMode !== 'cookie') {
            this._displayCookieIsNotSetMessage(googleMapInner, cookieIsNotSetMessage, false);
            this._setGoogleMapScript();
            return;
        }

        let cookieAccepted = CookieStorage.getItem('store-locator-cookie') !== false;

        if (updated) cookieAccepted = accepted;

        if (cookieAccepted === false) {
            this._displayCookieIsNotSetMessage(googleMapInner, cookieIsNotSetMessage);
        } else {
            this._displayCookieIsNotSetMessage(googleMapInner, cookieIsNotSetMessage, false);
            this._setGoogleMapScript();
        }
    }

    _displayCookieIsNotSetMessage(inner, message, display = true) {
        if (display) {
            if (message.classList.contains('d-none')) {
                message.classList.remove('d-none');
            }
            if (!inner.classList.contains('d-none')) {
                inner.classList.add('d-none');
            }
        } else {
            if (!message.classList.contains('d-none')) {
                message.classList.add('d-none');
            }
            if (inner.classList.contains('d-none')) {
                inner.classList.remove('d-none');
            }
        }
    }

    _setGoogleMapScript() {
        this.mapIntialization = DomAccess.querySelector(document, this.options.googleMapInitializationSelector, false);
        this.mapIntializationAll = DomAccess.querySelectorAll(document, this.options.googleMapInitializationSelector, false);
        if (!this.mapIntialization) return;

        let script = document.createElement('script');

        const value = this;

        this.mapIntialization.appendChild(script);

        script.onload = function () {
            value._afterScriptLoaded();
        };

        if (this.mapIntialization.children.length === 1) {
            script.src = 'https://maps.googleapis.com/maps/api/js?v=3.exp&key=' + this.options.apiKey;
        } else {
            document.$emitter.subscribe('afterScriptLoaded', () => {
                this._initialize();
            });
        }
    }

    _afterScriptLoaded() {
        document.$emitter.publish('afterScriptLoaded');
        this._initialize();

        return '';
    }

    _initialize() {
        this.form = DomAccess.querySelector(this.el, this.options.formSelector, false);
        this.initVars();
        this.registerEvents();
        this.createGoogleMap();
    }

    /**
     * Needed to assign "this"
     * Gets content of the stores nearby and processes this data
     */
    _onLoaded(response) {
        const res = JSON.parse(response);
        if (res.success === false) {
            if (res.error === 'no data') {
                const error = document.getElementById('error_data');
                error.style.display = 'flex';
            } else {
                const error = document.getElementById('error_permission');
                error.style.display = 'flex';
            }

        } else {
            //hide error messages again
            const errorData = document.getElementById('error_data');
            errorData.style.display = 'none';
            const errorPermission = document.getElementById('error_permission');
            errorPermission.style.display = 'none';
            //redraw google Map
            this.createGoogleMap();
            //get data for google maps
            this.deleteMarker();
            this.bounds = new this.maps.LatLngBounds();
            this.markers = this.createMarker(res.data);
            this.registerMarkerListeners(this.markers);
        }
        //center google Map
        if (this.form.elements['locator[place]'].value !== null || this.form.elements['locator[place]'].value !== '') {
            const geocoder = new this.maps.Geocoder();
            this.geocodeAddress(geocoder, this.map, this.form.elements['locator[place]'].value, false);
        }
    }

    /**
     * Helper method to insert the google-map into the container.
     */
    createGoogleMap() {
        this.mapDiv = DomAccess.querySelector(this.el, this.options.map, false);

        if (!this.mapDiv) {
            return;
        }

        //set map center to mapCenterLocation
        if (this.options.mapCenterLocation !== null || this.options.mapCenterLocation !== '' || this.options.storeLocatorElement === '1') {
            const geocoder = new this.maps.Geocoder();
            this.maps.event.addListener(window, 'load', () => {
                this.geocodeAddress(geocoder, this.map, this.options.mapCenterLocation, false);
            });
        }

        //if accepted sets marker to location of user
        if (navigator.geolocation && this.loadingMapOnce) {
            navigator.geolocation.getCurrentPosition((position) => {
                this.myPosition = new this.maps.LatLng(position.coords.latitude, position.coords.longitude);
                this.bounds.extend(this.myPosition);

                this.lat = DomAccess.querySelector(this.el, this.options.lat, false);
                this.lng = DomAccess.querySelector(this.el, this.options.lng, false);
                this.initSearch = DomAccess.querySelector(this.el, this.options.initSearch, false);

                //this.mapRequest();

                this.map = new this.maps.Map(this.mapDiv, {
                    zoom: parseInt(this.options.zoomFactor, 10),
                    center: this.myPosition,
                    streetViewControl: false,
                    zoomControl: true,
                    zoomControlOptions: {
                        position: this.google.maps.ControlPosition.LEFT_CENTER
                    }
                });

                if (this.options.homeIcon !== '') {
                    this.marker = new this.maps.Marker({
                        position: this.myPosition,
                        map: this.map,
                        icon: this.options.homeIcon,
                        optimized: false
                    });
                } else {
                    this.marker = new this.maps.Marker({
                        position: this.myPosition,
                        map: this.map,
                        icon: { url: 'https://maps.google.com/mapfiles/ms/icons/green-dot.png' }
                    });
                }

            });
            this.loadingMapOnce = false;
        }

        //set map center to defaultLocation
        if (!this.map) {
            this.map = new this.maps.Map(this.mapDiv, {
                zoom: parseInt(this.options.zoomFactor, 10),
                center: new this.maps.LatLng(this.options.defaultLat, this.options.defaultLng),
                streetViewControl: false,
                zoomControl: true,
                zoomControlOptions: {
                    position: this.google.maps.ControlPosition.LEFT_CENTER
                }

            });
            this.lat = DomAccess.querySelector(this.el, this.options.lat, false);
            this.lng = DomAccess.querySelector(this.el, this.options.lng, false);
            this.initSearch = DomAccess.querySelector(this.el, this.options.initSearch, false);

            this.mapRequest();
        }

        //set zoom factor depending on distance
        if(this.form.elements['locator[place]'].value){
            if (parseInt(this.form.elements['locator[distance]'].value, 10) < 100 && parseInt(this.options.zoomFactor, 10) > 3) {
                this.map.setZoom(parseInt(this.options.zoomFactor, 10) + 2);
            } else if (parseInt(this.form.elements['locator[distance]'].value, 10) === 100 && parseInt(this.options.zoomFactor, 10) > 2) {
                this.map.setZoom(parseInt(this.options.zoomFactor, 10) + 1);
            }
        } else {
            this.map.setZoom(parseInt(this.options.zoomFactor, 10));
        }
    }


    /**
     * Method to create a google map marker
     * @params data
     */
    createMarker(data) {
        this.markers = [];
        const entries = Object.values(Object.entries(data.elements));
        for (let i = 0; i < entries.length; i++) {
            let currentStore = entries[i][1];
            if (this.options.groupId === currentStore.storeGroupId || ((!this.options.groupId || this.options.groupId === '') && !this.options.storeDetailId) || this.options.storeDetailId === currentStore.id) {
                const pos = new this.maps.LatLng(currentStore.latitude, currentStore.longitude);
                let icon = '';
                if (currentStore && currentStore.storeGroup) {

                    let width = '';
                    let height = '';
                    let leftAnchor = '';
                    let rightAnchor = '';
                    let url = '';
                    if (currentStore.storeGroup.iconWidth) {
                        width = currentStore.storeGroup.iconWidth;
                    } else {
                        width = parseInt(this.options.iconWidth);
                    }
                    if (currentStore.storeGroup.iconHeight) {
                        height = currentStore.storeGroup.iconHeight;
                    } else {
                        height = parseInt(this.options.iconHeight);
                    }
                    if (currentStore.storeGroup.iconAnchorLeft) {
                        leftAnchor = currentStore.storeGroup.iconAnchorLeft;
                    } else {
                        leftAnchor = parseInt(this.options.iconAnchorLeft);
                    }
                    if (currentStore.storeGroup.iconAnchorRight) {
                        rightAnchor = currentStore.storeGroup.iconAnchorRight;
                    } else {
                        rightAnchor = parseInt(this.options.iconAnchorRight);
                    }
                    if (currentStore.storeGroup.icon && currentStore.storeGroup.icon.url) {
                        url = currentStore.storeGroup.icon.url;
                    } else {
                        url = this.options.storeIcon;
                    }
                    icon = {
                        origin: new google.maps.Point(0, 0),
                        url: url, // url
                        scaledSize: new google.maps.Size(width, height), // scaled size
                        anchor: new google.maps.Point(leftAnchor, rightAnchor),
                    };
                    if (!icon.url || icon.url === "") icon = '';
                } else {
                    if (this.options.storeIcon) {
                        icon = {
                            url: this.options.storeIcon, // url
                            scaledSize: new google.maps.Size(this.options.iconWidth, this.options.iconHeight), // scaled size
                            origin: new google.maps.Point(0, 0),
                            anchor: new google.maps.Point(this.options.iconAnchorLeft, this.options.iconAnchorRight),
                        };
                        if (!icon.url || icon.url === "") icon = '';
                    }
                }

                const markerPinColor = currentStore.handlerpoints === "Handler mit Cine-Produkten" ? "blue" : "red"; 

                const marker = new this.maps.Marker({
                    position: pos,
                    map: this.map,
                    icon: `https://maps.google.com/mapfiles/ms/icons/${markerPinColor}-dot.png`,
                    optimized: false
                });

                //set values for infowindow
                marker.set('companyName', currentStore.translated.name);
                marker.set('department', currentStore.translated.department);
                marker.set('street', currentStore.street);
                marker.set('zipcode', currentStore.zipcode);
                marker.set('city', currentStore.city);
                if (currentStore.country.translated.name != null || currentStore.country.translated.name !== '') {
                    marker.set('country', currentStore.country.translated.name);
                }
                if (currentStore.phone != null || currentStore.phone !== '') {
                    marker.set('phone', currentStore.translated.phone);
                }
                if (currentStore.email != null || currentStore.email !== '') {
                    marker.set('email', currentStore.translated.email);
                }
                if (currentStore.url != null || currentStore.url !== '') {
                    marker.set('url', currentStore.translated.url);
                }
                if (currentStore.opening_hours != null || currentStore.opening_hours !== '') {
                    marker.set('opening_hours', currentStore.translated.opening_hours);
                }

                this.markers.push(marker);
            }
        }
        return this.markers;
    }


    /**
     * Helper method to register a listener on the google-map-markers
     * @param markers
     */
    registerMarkerListeners(markers) {
        this.infoWindow = DomAccess.querySelector(this.el, this.options.infoWindow, false);
        const marker = Object.values(markers);

        for (let i = 0; i < marker.length; i++) {
            const constString = this.showMarkerInfo(marker[i]);

            marker[i].addListener('click', () => {
                //check if window is already open
                if (!this.isInfoWindowOpen(this.infoWindow.map)) {
                    this.infoWindow = new this.maps.InfoWindow({ content: constString });

                    // check encrypted email
                    if (this.options.encryptedMail) {
                        google.maps.event.addListener(this.infoWindow, 'domready', this._registerEncodedEmailEvent.bind(this));
                    }

                    this.infoWindow.open(this.map, marker[i]);
                } else {
                    //check if window has not same value as current open window
                    if (this.infoWindow.content !== constString) {
                        this.infoWindow = new this.maps.InfoWindow({ content: constString });

                        // check encrypted email
                        if (this.options.encryptedMail) {
                            google.maps.event.addListener(this.infoWindow, 'domready', this._registerEncodedEmailEvent.bind(this));
                        }

                        this.infoWindow.open(this.map, marker[i]);
                    } else {
                        this.infoWindow.close();
                    }
                }
            });
        }

        this.maps.event.addListener(this.map, 'click', () => {
            try {
                this.infoWindow.close();
            } catch (e) {
                return;
            }
        });
    }


    /**
     * Helper method to open only one google-maps-infowindow
     * @param map
     * @return {boolean}
     */
    isInfoWindowOpen(map) {
        return (map !== null && typeof map !== 'undefined');
    }


    /**
     * Helper method to display shop information in the google-maps-infowindow
     * @param marker
     * @return {string}
     */
    showMarkerInfo(marker) {
        const companyName = marker.get('companyName');
        const department = marker.get('department');
        const street = marker.get('street');
        const zipcode = marker.get('zipcode');
        const city = marker.get('city');
        const country = marker.get('country');
        const phone = marker.get('phone');
        const opening = marker.get('opening_hours');
        let email = marker.get('email');
        let url = marker.get('url');
        let htmlcontent = ' ';

        if (this.options.showCompanyName === '1' && companyName.trim()) {
            if (this.options.storeInformationHeadline === 'h1') {
                htmlcontent = '<h1><strong>' + companyName + '</strong></h1>'
            }
            if (this.options.storeInformationHeadline === 'h2') {
                htmlcontent = '<h2><strong>' + companyName + '</strong></h2>'
            }
            if (this.options.storeInformationHeadline === 'h3') {
                htmlcontent = '<h3><strong>' + companyName + '</strong></h3>'
            }
            if (this.options.storeInformationHeadline === 'h4') {
                htmlcontent = '<h4><strong>' + companyName + '</strong></h4>'
            }
            if (this.options.storeInformationHeadline === 'h5') {
                htmlcontent = '<h5><strong>' + companyName + '</strong></h5>'
            }
            if (this.options.storeInformationHeadline === 'h6') {
                htmlcontent = '<h6><strong>' + companyName + '</strong></h6>'
            }
        }
        if (this.options.showDepartment === '1' && department != null && department.trim()) {
            htmlcontent = htmlcontent + '<span>' + department + '</span><br>'
        }
        if (this.options.showStreet === '1' && street.trim()) {
            htmlcontent = htmlcontent + '<span>' + street + '</span><br>'
        }

        if (this.options.showZipcode === '1' && this.options.showCity === '1' && zipcode.trim() && city.trim()) {
            if (zipcode.trim() && city.trim()) {
                htmlcontent = htmlcontent + '<span>' + zipcode + ' ' + city + '</span><br>'
            } else if (!zipcode.trim() && city.trim()) {
                htmlcontent = htmlcontent + '<span>' + city + '</span><br>'
            } else if (zipcode.trim() && !city.trim()) {
                htmlcontent = htmlcontent + '<span>' + zipcode + '</span><br>'
            }
        } else if (this.options.showZipcode === '1' && this.options.showCity !== '1' && zipcode.trim()) {
            htmlcontent = htmlcontent + '<span>' + zipcode + '</span><br>'
        } else if (this.options.showZipcode !== '1' && this.options.showCity !== '1' && city.trim()) {
            htmlcontent = htmlcontent + '<span>' + city + '</span><br>'
        }
        if (this.options.showCountry === '1' && country.trim()) {
            htmlcontent = htmlcontent + '<span>' + country + '</span><br>'
        }
        htmlcontent = htmlcontent + '<div style="margin-top:2%;"></div>';
        if (this.options.showPhoneNumber === '1' && phone != null && phone.trim()) {
            const phoneTag = phone.replace(/\s/g, '');
            htmlcontent = htmlcontent + '<span><strong><a href="tel:' + phoneTag + '">' + phone + '</a></strong></span><br>'
        }
        if (this.options.showMail === '1' && email != null && email.trim()) {
            if (this.options.encryptedMail) {
                email = this._encryptStoreData(email);
            }

            htmlcontent = htmlcontent + '<span><strong><a class="' + this.options.mailClass.substring(1) + '" data-mail="' + email + '" href="mailto:' + email + '" target="_blank">' + '<span className="icon icon-envelope"> <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="24" height="24" viewBox="0 0 24 24"><defs><path d="m3.7438 5 7.1093 4.9765a2 2 0 0 0 2.2938 0L20.2562 5H3.7438zM22 6.2207l-7.7062 5.3943a4 4 0 0 1-4.5876 0L2 6.2207V18c0 .5523.4477 1 1 1h18c.5523 0 1-.4477 1-1V6.2207zM3 3h18c1.6569 0 3 1.3431 3 3v12c0 1.6569-1.3431 3-3 3H3c-1.6569 0-3-1.3431-3-3V6c0-1.6569 1.3431-3 3-3z" id="icons-default-envelope"></path></defs><use xlink:href="#icons-default-envelope" fill="#758CA3" fill-rule="evenodd"></use></svg></span>' + '</a></strong></span><br>'
        }

        if (this.options.showURL === '1' && url != null && url.trim()) {
            if (url.includes('https://') !== true && url.includes('http://') !== true) {
                url = 'https://' + url;
            }
            htmlcontent = htmlcontent + '<span><strong><a href="' + url + '" target="_blank">' + url.replace(/^(?:https?:\/\/)/, '') + '</a></strong></span><br>'
        }
        if (this.options.showOpening === '1' && opening != null && opening.trim()) {
            htmlcontent = htmlcontent + '<div style="margin-top:6%; margin-bottom: -4%;">' + opening + '</div><br>'
        }

        return '<div id="content" class="acris-store-locator-detail-information" style="margin:4%; min-width: ' + this.options.widthInfowindow + 'px; min-height: ' + this.options.heightInfowindow + 'px;">' + htmlcontent + '</div>';
    }

    /**
     * Helper method to delete markers
     */
    deleteMarker() {
        this.infoWindow = DomAccess.querySelector(this.el, this.options.infoWindow, false);

        for (var i = 0; i < this.markers.length; i++) {
            this.markers[i].setMap(null);
        }
    }

    _registerEncodedEmailEvent() {
        const event = (DeviceDetection.isTouchDevice()) ? 'touchstart' : 'click';
        this.mail = DomAccess.querySelectorAll(this.el, this.options.mailClass, false);

        if (this.mail && this.mail.length > 0) {
            for (let i = 0; i < this.mail.length; i++) {
                this.mail[i].addEventListener(event, this._onSendingEmail.bind(this, this.mail[i]));
            }
        }
    }

    _onSendingEmail(mail, event) {
        event.preventDefault();
        if (mail && mail.dataset && mail.dataset.mail) {
            let encodedData = mail.dataset.mail;
            let decodedData = encodedData.replace(/[a-zA-Z]/g, function (char) { //foreach character
                return String.fromCharCode( //decode string
                    (char <= "Z" ? 90 : 122) >= (char = char.charCodeAt(0) + 10) ? char : char - 26
                );
            });
            location.href = this.options.mailTo + decodedData;
        }
    }

    _encryptStoreData(str, rotation = 16, map = {}) {
        const table = {};  // New table, to avoid mutating the parameter passed in
        // Establish mappings for the characters passed in initially
        for (var key in map) {
            table[map[key]] = key;
            table[key] = map[key];
        }
        // Then build the rotation map.
        // 65 and 97 are the character codes for A and a, respectively.
        for (var i = 0; i < 26; i++) {
            table[String.fromCharCode(65 + i)] = String.fromCharCode(65 + (i + rotation) % 26);
            table[String.fromCharCode(97 + i)] = String.fromCharCode(97 + (i + rotation) % 26);
        }

        return str.split('').map((c) => table[c] || c).join('');
    }
}
