function ServicepointManager(carrier) {
    this.carrier = carrier;

    this.getServicepoints = function (country, zip_code, street) {
        var carrier = this.carrier;

        new Ajax.Request(baseUrl + 'coolrunner/droppoint/getDroppointsFromPostalCode/carrier/' + this.carrier, {
            method: 'get',
            parameters: {
                'zip_code': zip_code,
                'country_code': country
            },
            onComplete: function (transport) {
                var data = transport.responseText.evalJSON();

                if(data && data.status.toLowerCase() === 'ok') {
                    var servicepoints = data.result;

                    console.log(servicepoints);
                }
            }
        });
    }
}


CoolRunner = function (code) {
    this.code = code;
    this.prefix = code.replace("_", "-") + "-",
        this.methodcode = "s_method_" + code,
        this.marker_icon = false,
        this.setMarkerIcon = function (icon) {
            this.marker_icon = icon;
        },
        this.checkDroppointShipping = function () {
            var self = this;
            var show = false;
            var showid = false;
            $$("input[name='shipping_method']").each(function (v) {
                if (v.checked) {
                    if (v.id.search(self.methodcode) >= 0 && v.id.search("droppoint") >= 0) {
                        var id = v.id.replace(self.methodcode + "_", "");
                        var i = 0;
                        while (i < self.idArrayDroppoints.length) {
                            if (id == self.idArrayDroppoints[i]) {
                                show = true;
                                showid = i;
                                break;
                            }
                            i++;
                        }
                    }
                }
                if (self.addEventToElement(v, 'click', self.code + '_droppoint_form_change')) {
                    Event.observe($(v.id), 'change', function droppoint_form_change(event) {
                        if (this.id.search(self.methodcode) >= 0 && this.id.search("droppoint") >= 0) {
                            var id = this.id.replace(self.methodcode + "_", "");
                            if (this.checked && self.idArrayDroppoints.indexOf(id) >= 0) {
                                if (this.parentNode.className == 'no-display') {
                                    this.parentNode.parentNode.appendChild($(self.prefix + 'droppoint-form'));
                                } else {
                                    this.parentNode.appendChild($(self.prefix + 'droppoint-form'));
                                }
                                $(self.prefix + 'droppoint-form').style.display = "block";
                            } else {
                                $(self.prefix + 'droppoint-form').style.display = "none";
                            }
                        } else {
                            $(self.prefix + 'droppoint-form').style.display = "none";
                        }
                    });
                }
            });
            if (show && showid !== false) {
                var input = $(self.methodcode + '_' + self.idArrayDroppoints[showid]);
                if (input.parentNode.className == 'no-display') {
                    input.parentNode.parentNode.appendChild($(self.prefix + 'droppoint-form'));
                } else {
                    input.parentNode.appendChild($(self.prefix + 'droppoint-form'));
                }
                $(self.prefix + 'droppoint-form').style.display = "block";
                self.setDroppointFromLocalStorage();
            } else {
                $(self.prefix + 'droppoint-form').style.display = "none";
            }

        },
        this.setDroppointFromLocalStorage = function () {
            if (typeof localStorage != 'undefined') {
                if (localStorage.getItem(this.prefix + 'servicepoint') !== null) {
                    var servicePoint = JSON.parse(localStorage.getItem(this.prefix + 'servicepoint'));
                    var servicePointId = servicePoint.droppoint_id;
                    var radio = {value: servicePointId};
                    servicePointsLocal = [servicePoint];

                    this.setAddress(radio, servicePointsLocal);
                    if (!lastUpdatedDroppointId || lastUpdatedDroppointId != servicePointId) {
                        this.updateShippingAddress();
                    }
                }
            }
        },
        this.loadDroppoints = function () {
            var postalCode = document.getElementById(this.prefix + 'postalCode');
            var countryCode = document.getElementById(this.prefix + 'countryCode');
            this.getJson(postalCode.value, countryCode.value);
        },
        this.setMobileHeight = function () {

            currentScrollPosition = document.viewport.getScrollOffsets()[1];

            $(document.body).setStyle({
                position: 'fixed',
                top: -currentScrollPosition + 'px',
                width: '100%'
            });
        },
        this.onEnterGetJson = function (e) {
            if (e.keyCode == 13) {
                this.loadDroppoints();
                return false;
            }
        },
        this.setDroppointSizes = function () {
            var viewHeight = document.viewport.getHeight();

            var wrapperHeight = $(this.prefix + 'droppoint-wrapper').getHeight();
            var headerHeight = $(this.prefix + 'droppoint-header').getHeight();
            var buttonsHeight = $(this.prefix + 'droppoint-buttons-set').getHeight();

            var containerHeight = wrapperHeight - headerHeight - buttonsHeight;
            var containerWidthHalf = $(this.prefix + 'address-container').getWidth();

            $(this.prefix + 'droppoint-wrapper').setStyle({
                top: ((viewHeight - wrapperHeight) / 2) + 'px',
            });
            $(this.prefix + 'droppoint-result').setStyle({
                height: containerHeight + 'px'
            });
            $(this.prefix + 'droppoint-result').down('.map').setStyle({
                height: containerHeight + 'px'
            });
            $(this.prefix + 'address-container').setStyle({
                height: containerHeight + 'px'
            });
            $(this.prefix + 'opening-container').setStyle({
                height: containerHeight + 'px',
            });
        },
        this.getJson = function (postalCode, countryCode) {
            $(this.prefix + 'loading').style.display = "inline";
            if (typeof baseUrl == 'undefined') {
                baseUrl = '/';
            }
            self = this;
            new Ajax.Request(baseUrl + 'coolrunner/droppoint/getDroppointsFromPostalCode/carrier/' + this.code, {
                method: 'get',
                parameters: {
                    'postalCode': postalCode,
                    'countryCode': countryCode
                },
                onComplete: function (transport) {
                    $(self.prefix + 'loading').style.display = "none";

                    jsonResponse = transport.responseText.evalJSON();
                    if (jsonResponse && jsonResponse.status == "ok") {
                        $(self.prefix + 'droppoint-overlay').style.display = 'block';

                        self.setDroppointSizes();


                        if (self.addEventToElement($(self.prefix + 'close-droppoint-selector-save'), 'click', self.code + '_move_overlay_click')) {
                            Event.observe($(self.prefix + 'close-droppoint-selector-save'), 'click', function move_overlay_click(event) {
                                $(self.prefix + 'droppoint-overlay').style.display = "none";
                                $(self.prefix + 'droppoint-form').style.display = "block";
                                self.moveOverlayIn();

                                $(document.body).setStyle({
                                    position: 'inherit'
                                });

                                window.scrollTo(0, currentScrollPosition);

                            });
                        }
                        if (self.addEventToElement($(self.prefix + 'close-droppoint-selector-remove'), 'click', self.code + '_remove_overlay_click')) {
                            Event.observe($(self.prefix + 'close-droppoint-selector-remove'), 'click', function remove_overlay_click(event) {
                                $(self.prefix + 'droppoint-overlay').style.display = "none";
                                $(self.prefix + 'droppoint-form').style.display = "block";
                                if ($$("input:checked[id^='" + self.prefix + "servicePointId'][type='radio']").length) {
                                    $$("input:checked[id^='" + self.prefix + "servicePointId'][type='radio']")[0].checked = false;
                                }
                                self.selectedServicePoint = null;
                                self.setAddress(false, self.servicePoints);


                                self.moveOverlayIn();

                                $(document.body).setStyle({
                                    position: 'inherit'
                                });

                                window.scrollTo(0, currentScrollPosition);

                            });
                        }

                        self.moveOverlayOut();
                        self.handleJson(jsonResponse);
                        self.setMobileHeight();
                    } else {
                        alert('Ingen afhentningssteder fundet for det indtastede postnummer, prøv venligst igen...');
                        $(self.prefix + 'postalcode').focus();
                    }
                }
            });
        },
        this.updateShippingAddress = function () {
            if (typeof localStorage != 'undefined' && typeof this.selectedServicePoint != 'undefined' && this.selectedServicePoint !== null) {
                try {
                    localStorage.setItem(this.prefix + 'servicepoint', JSON.stringify(this.selectedServicePoint));
                } catch (e) {
                }
                this.selectedServicePoint = null;
            }
            if (typeof baseUrl == 'undefined') {
                baseUrl = '/';
            }
        },
        this.moveOverlayOut = function () {
            $(document.body).appendChild($(this.prefix + 'droppoint-overlay'));
        },
        this.moveOverlayIn = function () {
            $(this.prefix + 'droppoint-form').appendChild($(this.prefix + 'droppoint-overlay'));
            this.updateShippingAddress();
        },
        this.handleJson = function (jsonResponse) {
            mapCenterCoords = this.getMapBound(jsonResponse.result);
            sw = mapCenterCoords[0];
            ne = mapCenterCoords[1];
            mapOptions = {
                //center: ne,
                zoom: 13,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                draggable: false,
                zoomControl: true,
                scrollwheel: false,
                disableDoubleClickZoom: true,
                keyboardShortcuts: false,
                streetViewControl: false
            };
            map = new google.maps.Map(document.getElementById(this.prefix + "map-canvas"), mapOptions);
            map.fitBounds(new google.maps.LatLngBounds(sw, ne));
            this.servicePoints = jsonResponse.result;
            this.markers = this.addDroppointMarkers(jsonResponse.result, map);
        },
        this.addDroppointMarkers = function (servicePoints, map) {
            var markers = [];
            $(this.prefix + 'addresses').update("");
            var x = 0;
            while (x < servicePoints.length) {
                var classname = "";
                if (x % 5 === 0) {
                    classname = " first";
                } else if ((x + 1) % 5 === 0) {
                    classname = " last";
                }
                var marker = this.addMarker(servicePoints[x], map);
                this.addAddress(servicePoints[x], classname);
                markers.push(marker);
                x++;
            }

            return markers;
        },
        this.addAddress = function (servicePoint, classname) {
            self = this;
            var servicePointId = servicePoint.droppoint_id;
            var isChecked = false;
            if ($(this.prefix + 'droppoint-id').value == servicePointId) {
                isChecked = true;
                this.setOpeningHours(servicePointId);
            }
            var container = new Element('li', {
                'id': servicePointId
            });
            container.addClassName(this.prefix + "container" + classname);
            var radio = new Element('input', {
                'type': "radio",
                'id': this.prefix + "servicePointId-" + servicePointId,
                'value': servicePointId
            });
            radio.addClassName(this.prefix + "radio");
            radio.name = "droppoint";
            if (isChecked) {
                radio.checked = "checked";
            }
            var address = new Element('div');
            address.addClassName(this.prefix + "address");
            var label = new Element('label', {
                'for': this.prefix + "servicePointId-" + servicePointId
            });
            var name = new Element('h4').update(servicePoint.name);
            var droppointaddress = new Element('div').update('<p>' + servicePoint.address.street + "</p><p>" + servicePoint.address.postal_code + " " + servicePoint.address.city + '</p>');
            droppointaddress.addClassName(this.prefix + "content");

            Event.observe(radio, 'change', function (event) {
                self.bounceMarkerOnRadioClick(this);
                self.setAddress(this, self.servicePoints);
            });
            label.appendChild(name);
            label.appendChild(droppointaddress);
            address.appendChild(label);
            container.appendChild(radio);
            container.appendChild(address);
            $(this.prefix + 'addresses').appendChild(container);
        },
        this.addMarker = function (servicePoint, map) {
            self = this;
            var servicePointId = servicePoint.droppoint_id;
            var myLatlng = new google.maps.LatLng(servicePoint.coordinate.latitude, servicePoint.coordinate.longitude);
            var image = this.marker_icon;
            var marker = new google.maps.Marker({
                position: myLatlng,
                title: servicePoint.name,
                servicePointId: this.prefix + 'servicePointId-' + servicePointId
                //icon: image
            });

            marker.setMap(map);
            google.maps.event.addListener(marker, 'click', function () {
                self.toggleBounce(self, this);
            });
            return marker;
        },
        this.toggleBounce = function (coolrunner, marker) {
            var x = 0;
            while (x < coolrunner.markers.length) {
                coolrunner.markers[x].setAnimation(null);
                x++;
            }
            marker.setAnimation(google.maps.Animation.BOUNCE);
            coolrunner.selectRadioOnMarkerClick(marker);
        },
        this.selectRadioOnMarkerClick = function (marker) {
            $(marker.servicePointId).checked = true;

            this.setAddress($(marker.servicePointId), this.servicePoints);
            this.setOpeningHours(marker.servicePointId.replace(this.prefix + "servicePointId-", ""));
            var x = 0;
            while (x < this.markers.length) {
                this.markers[x].setAnimation(null);
                x++;
            }
            marker.setAnimation(google.maps.Animation.BOUNCE);
        },
        this.bounceMarkerOnRadioClick = function (radio) {
            var bouncex = false;
            var x = 0;
            while (x < this.markers.length) {
                this.markers[x].setAnimation(null);
                if (this.markers[x].servicePointId == radio.id) {
                    bouncex = x;
                    this.setOpeningHours(radio.value);
                }
                x++;
            }
            if (bouncex !== false) {
                this.markers[bouncex].setAnimation(google.maps.Animation.BOUNCE);
            }
        },
        this.setOpeningHours = function (servicePointId) {
            var openingheader = new Element('div');
            openingheader.addClassName(this.prefix + "opening-header");
            openingheader.innerHTML = $(this.prefix + "opening-header-text").innerHTML;

            $(this.prefix + 'opening-container').innerHTML = "";
            $(this.prefix + 'opening-container').appendChild(openingheader);
            if (this.servicePoints) {
                var x = 0;
                while (x < this.servicePoints.length) {
                    if (this.servicePoints[x].droppoint_id == servicePointId) {
                        var droppointaddress = new Element('div').update('<span>' + this.servicePoints[x].name + '</span><br />' + this.servicePoints[x].address.street + "<br />" + this.servicePoints[x].address.postal_code + " " + this.servicePoints[x].address.city + '<br />');
                        droppointaddress.addClassName(this.prefix + "content");
                        $(this.prefix + 'opening-container').appendChild(droppointaddress);

                        if (this.servicePoints[x].opening_hours) {
                            var openinghourstext = new Element('span');
                            openinghourstext.innerHTML = $(this.prefix + "opening-hours-text").innerHTML;
                            $(this.prefix + 'opening-container').appendChild(openinghourstext);
                            var openinghourslist = new Element('ul');
                            for (var property in this.servicePoints[x].opening_hours) {
                                if (this.servicePoints[x].opening_hours.hasOwnProperty(property)) {
                                    var li = new Element('li');
                                    var weekdaytext = $(this.prefix + "opening-hours-weekday-" + this.servicePoints[x].opening_hours[property].weekday.toLowerCase()).innerHTML;
                                    var weekday = new Element('div').addClassName(this.prefix + "weekday").update(weekdaytext);

                                    var openinghours = new Element('div').addClassName(this.prefix + "openinghours");
                                    var from = new Element('span').addClassName("from").update(this.servicePoints[x].opening_hours[property].from);
                                    var to = new Element('span').addClassName("to").update(this.servicePoints[x].opening_hours[property].to);
                                    openinghours.appendChild(from)
                                    openinghours.appendChild(to);
                                    li.appendChild(weekday);
                                    li.appendChild(openinghours);
                                    openinghourslist.appendChild(li);
                                }
                            }
                            $(this.prefix + 'opening-container').appendChild(openinghourslist);
                        }
                        break;
                    }
                    x++;
                }
            }
        },
        this.setAddress = function (radio, servicePointsArray) {
            if (radio && servicePointsArray) {
                var servicePointId = radio.value;
                var x = 0;
                while (x < servicePointsArray.length) {

                    if (servicePointsArray[x].droppoint_id == servicePointId) {
                        $(this.prefix + "droppoint-id").value = servicePointId;
                        if ($(this.prefix + "advice-required-entry-droppoint-id")) {
                            $(this.prefix + "advice-required-entry-droppoint-id").remove();
                        }

                        $(this.prefix + "droppoint-name").value = servicePointsArray[x].name;
                        $(this.prefix + "droppoint-city").value = servicePointsArray[x].address.city;
                        $(this.prefix + "droppoint-postalcode").value = servicePointsArray[x].address.postal_code;
                        $(this.prefix + "droppoint-streetname").value = servicePointsArray[x].address.street;
                        $(this.prefix + 'selected-droppoint').innerHTML = $(this.prefix + "selected-droppoint-text-container-selected").innerHTML + servicePointsArray[x].name + "<br>" + servicePointsArray[x].address.street + "<br>" + servicePointsArray[x].address.postal_code + " " + servicePointsArray[x].address.city;

                        this.selectedServicePoint = servicePointsArray[x];
                        break;
                    }
                    x++;
                }
            } else {
                $(this.prefix + "droppoint-id").value = "";
                $(this.prefix + "droppoint-name").value = "";
                $(this.prefix + "droppoint-city").value = "";
                $(this.prefix + "droppoint-postalcode").value = "";
                $(this.prefix + "droppoint-streetname").value = "";
                $(this.prefix + 'selected-droppoint').innerHTML = $(this.prefix + "selected-droppoint-text-container").innerHTML;
                this.selectedServicePoint = null;
            }
        },
        this.getMapBound = function (servicePoints) {
            centerNMin = 9999999;
            centerNMax = -9999999;
            centerEMin = 9999999;
            centerEMax = -9999999;
            var x = 0;
            while (x < servicePoints.length) {
                if (parseFloat(servicePoints[x].coordinate.latitude) > centerNMax) {
                    centerNMax = parseFloat(servicePoints[x].coordinate.latitude);
                }
                if (parseFloat(servicePoints[x].coordinate.latitude) < centerNMin) {
                    centerNMin = parseFloat(servicePoints[x].coordinate.latitude);
                }
                if (parseFloat(servicePoints[x].coordinate.longitude) > centerEMax) {
                    centerEMax = parseFloat(servicePoints[x].coordinate.longitude);
                }
                if (parseFloat(servicePoints[x].coordinate.longitude) < centerEMin) {
                    centerEMin = parseFloat(servicePoints[x].coordinate.longitude);
                }
                x++;
            }
            var coords = [];
            coords.push(new google.maps.LatLng(centerNMin, centerEMin));
            coords.push(new google.maps.LatLng(centerNMax, centerEMax));
            return coords;
        },
        this.updateAfhenter = function (str) {
            this.saveValueToLocalStorage(this.prefix + 'afhenter', str);
        },
        this.saveValueToLocalStorage = function (key, value) {
            if (typeof localStorage != 'undefined') {
                try {
                    localStorage.setItem(key, value);
                    return true;
                } catch (e) {
                }
            }
            return false;
        },
        this.getValueFromLocalStorage = function (key) {
            if (typeof localStorage != 'undefined') {
                try {
                    if (localStorage.getItem(key) !== null) {
                        return localStorage.getItem(key);
                    }
                } catch (e) {
                }
            }
            return false;
        },
        this.addEventToElement = function (element, event_name, function_name) {

            var addEvent = true;
            if ($(element).readAttribute("data-event-" + function_name)) {
                addEvent = false;
            } else {
                $(element).writeAttribute("data-event-" + function_name, 1);
            }
            return addEvent;
        }
}; // coolrunner slut