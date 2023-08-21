define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/url', 'local_eduvidual/main'], function($, AJAX, NOTIFICATION, STR, URL, MAIN) {
    return {
        debug: 1,
        debug_gps: 1,
        cache_orgs: {}, // for caching search results of manageorgs_search
        refresh_identifier: 0,
        varcache: undefined,

        /**
        * Sets the blockfooter of the eduvidual block
        **/
        blockfooter: function(notimeout) {
            if (this.debug > 0) console.log('ADMIN.blockfooter()');
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.watchValue({
                    target: '#local_eduvidual_admin_blockfooter',
                    run: function() {
                        var o = this;
                        require(['local_eduvidual/main'], function(MAIN) {
                            MAIN.connect({ module: 'admin', act: 'blockfooter', blockfooter: $(o.target).val() }, { signalItem: $(o.target) });
                        });
                    }
                });
            });
        },
        /**
         * Helper for next- and previous-button in coursedeletelog.
         * @param act 'next' or 'previous'
         * @param uniqid the uniqid of the parent div.
         */
        coursedeleteform: function(act, uniqid) {
            var size = Math.round($('#' + uniqid + ' select[name=size]').val());
            var from = Math.round($('#' + uniqid + ' input[name=from]').val());
            var to = (act == 'next') ? from + size : from - size;
            if (to < 0) to = 0;

            $('#' + uniqid + ' input[name=from]').val(to);
            $('#' + uniqid + ' form').submit();
        },
        /**
         * Triggers when the search-field in admin_orgs is used.
         * @param uniqid of the template.
         * @param id of org (NOT orgid)
         */
        manageorgs_open: function(uniqid, id) {
            var container = $('#' + uniqid + '-form');
            $(container).find('.local_eduvidual_signal_error').removeClass('local_eduvidual_signal_error');
            var org = this.cache_orgs[id];
            if (typeof org !== 'undefined') {
                var fields = Object.keys(org);
                for (var a = 0; a < fields.length; a++) {
                    var field = fields[a];
                    $('#' + uniqid + '-field-' + field).attr('data-orig', org[field]).val(org[field]);
                }
                $('#' + uniqid + '-field-manage').attr('data-orig', org['orgid']);
            } else {
                container.find('input').val('').attr('data-orig', '');
            }

            if (typeof org !== 'undefined' && typeof org['categoryid'] !== 'undefined' && org['categoryid'] > 0) {
                $('#' + uniqid + '-field-orgid').attr('readonly', 'readonly');
                $('#' + uniqid + '-field-categoryid').css('display', 'block');
                $('#' + uniqid + '-field-manage').css('display', 'block');
            } else {
                $('#' + uniqid + '-field-orgid').removeAttr('readonly');
                $('#' + uniqid + '-field-categoryid').css('display', 'none');
                $('#' + uniqid + '-field-manage').css('display', 'none');
            }

        },
        /**
         * Resets the form.
         * @param uniqid of the template.
         */
        manageorgs_reset: function(uniqid) {
            this.manageorgs_open(uniqid, 0);
        },
        /**
         * Triggers when the search-field in admin_orgs is used.
         * @param uniqid of the template.
         */
        manageorgs_search: function(uniqid) {
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.watchValue({
                    target: '#' + uniqid + '-search',
                    run: function() {
                        var o = this;
                        require(['local_eduvidual/main'], function(MAIN) {
                            MAIN.connect({ module: 'admin', act: 'manageorgs_search', search: $(o.target).val() }, { signalItem: $(o.target), uniqid: uniqid });
                        });
                    }
                });
            });
        },
        /**
         * Stores an organisation.
         * @param uniqid of the template.
         */
        manageorgs_store: function(uniqid) {
            var container = $('#' + uniqid + '-form');
            $(container).find('.local_eduvidual_signal_error').removeClass('local_eduvidual_signal_error');
            var fields = {};
            container.find('input').each(function(){
                if (typeof $(this).attr('id') !== 'undefined' && $(this).attr('id') != '') {
                    var field = $(this).attr('id').replace(uniqid + '-field-', '');
                    fields[field] = $(this).val();
                }
            });
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'admin', act: 'manageorgs_store', fields: fields }, { signalItem: $('#' + uniqid + '-store'), uniqid: uniqid });
            });
        },
        /**
        * Sets the navbar of the eduvidual block
        **/
        navbar: function() {
            if (this.debug > 0) console.log('ADMIN.navbar()');
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.watchValue({
                    target: '#local_eduvidual_admin_navbar',
                    run: function() {
                        var o = this;
                        require(['local_eduvidual/main'], function(MAIN) {
                            MAIN.connect({ module: 'admin', act: 'navbar', navbar: $(o.target).val() }, { signalItem: $(o.target) });
                        });
                    }
                });
            });
        },
        /**
         * Load orgs in a specific rectangle.
         * @param uniqid of mustache used.
         * @param lon1 longitude 1 of rectangle.
         * @param lon2 longitude 2 of rectangle.
         * @param lat1 latitude 1 of rectangle.
         * @param lat2 latitude 2 of rectangle.
         */
        org_gps: function(uniqid, lon1, lon2, lat1, lat2) {
            var ADMIN = this;
            ADMIN.refresh_identifier++;
            var refresh_identifier = ADMIN.refresh_identifier;
            if (typeof uniqid === 'undefined') uniqid = $('.local_eduvidual_admin_map').attr('id');
            if (typeof lon1 === 'undefined') lon1 = -200;
            if (typeof lon2 === 'undefined') lon2 = 200;
            if (typeof lat1 === 'undefined') lat1 = -200;
            if (typeof lat2 === 'undefined') lat2 = 200;

            console.log('ADMIN.org_gps(uniqid, lon1, lon2, lat1, lat2)', uniqid, lon1, lon2, lat1, lat2);
            var includenonegroup = $('#' + uniqid + '-include-nonegroup').prop('checked') ? 1 : 0;
            var method = 'local_eduvidual_admin_org_gps';
            var data = { lon1: lon1, lon2: lon2, lat1: lat1, lat2: lat2, includenonegroup: includenonegroup, advanceddata: 0 };
            if (typeof ADMIN.map !== 'undefined') {
                ADMIN.map.remove();
            }

            if (ADMIN.debug_gps) console.log('Sending to ' + method, data);
            //MAIN.spinnerGrid(true);
            AJAX.call([{
                methodname: method,
                args: data,
                done: function(data) {
                    if (ADMIN.refresh_identifier != refresh_identifier) return; // Immediately abort!
                    try { data = JSON.parse(data); } catch (e) {}
                    if (ADMIN.debug_gps) console.log('Got response', data);
                    //MAIN.spinnerGrid(false);

                    ADMIN.orgs = data;
                    ADMIN.org_gps_list(uniqid);
                },
                fail: NOTIFICATION.exception
            }]);
        },
        /**
         * Set the bounds of the current map.
         * @param uniqid of mustache used.
         */
        org_gps_bounds: function(uniqid) {
            var ADMIN = this;
            if (typeof ADMIN.map === 'undefined') return;
            var bounds = ADMIN.map.getBounds().toBBoxString().split(',');
            if (ADMIN.debug_gps) console.log('Setting bounds to', bounds);
            $('#' + uniqid).attr('data-lon1', bounds[0]);
            $('#' + uniqid).attr('data-lon2', bounds[2]);
            $('#' + uniqid).attr('data-lat1', bounds[1]);
            $('#' + uniqid).attr('data-lat2', bounds[3]);

            // If no movement in 1 second, refresh stats.
            if (typeof ADMIN.map_moved === 'undefined') {
                ADMIN.map_moved = 0;
            } else {
                ADMIN.map_moved++;
                var compare = ADMIN.map_moved;
                setTimeout(function(){
                    if (compare == ADMIN.map_moved) {
                        ADMIN.org_gps_refresh(uniqid);
                    }
                }, 1000);
            }
        },
        /**
         * Does the listing on the map and tables.
         * @param uniqid
         */
        org_gps_list: function(uniqid) {
            var ADMIN = this;
            if (ADMIN.debug_gps) console.log('ADMIN.org_gps_list(uniqid)', uniqid);
            if (typeof ADMIN.map !== 'undefined') {
                ADMIN.map.remove();
            }
            var refresh_identifier = ADMIN.refresh_identifier;
            var includenonegroup = $('#' + uniqid + '-include-nonegroup').prop('checked') ? 1 : 0;
            $('#' + uniqid + '-legend td.none').parent().css('display', includenonegroup ? 'table-row' : 'none');
            $('#' + uniqid + '-legend-none').css('display', includenonegroup ? 'inline-block' : 'none');
            var orgs = ADMIN.orgs;

            var timerefdate = new Date(
                $('#' + uniqid + '-year').val(),
                $('#' + uniqid + '-month').val()-1,
                $('#' + uniqid + '-day').val(),
                $('#' + uniqid + '-hour').val(),
                $('#' + uniqid + '-minute').val(),
                $('#' + uniqid + '-second').val(),
                0
            );
            $('#' + uniqid + '-second').val(timerefdate.getSeconds());
            $('#' + uniqid + '-minute').val(timerefdate.getMinutes());
            $('#' + uniqid + '-hour').val(timerefdate.getHours());
            $('#' + uniqid + '-day').val(timerefdate.getDate());
            $('#' + uniqid + '-month').val(timerefdate.getMonth()+1);
            $('#' + uniqid + '-year').val(timerefdate.getFullYear());
            var timeref = Math.floor(timerefdate.getTime()/1000);

            var smallest_lat = 200;
            var smallest_lon = 200;
            var biggest_lat = -200;
            var biggest_lon = -200;
            var counts = {
                'both': 0,
                'eduv': 0,
                'none': 0,
            };

            var layers = [
                L.layerGroup(),
                L.layerGroup(),
                L.layerGroup(),
                L.layerGroup(),
            ];

            var districttypes = {
                0: '',
                1: 'Burgenland',
                2: 'Kärnten',
                3: 'Niederösterreich',
                4: 'Oberösterreich',
                5: 'Salzburg',
                6: 'Steiermark',
                7: 'Tirol',
                8: 'Vorarlberg',
                9: 'Wien',
            };

            var orgtypes = {
                0: 'Sonstige',
                1: 'VS',
                2: 'MS',
                3: 'Sonderschule',
                4: 'PTS',
                5: 'BS',
                6: 'Gymnasium',
                7: 'HTL',
                8: 'HAK',
                9: 'HUM',
            };

            Object.keys(orgs).forEach(function(i){
                console.log('refresh_identifier', ADMIN.refresh_identifier, refresh_identifier);
                if (ADMIN.refresh_identifier != refresh_identifier) return; // Immediately abort!
                orgs[i].url = URL.relativeUrl('/local/eduvidual/pages/myorgs.php?orgid=' + orgs[i].orgid); // Set an url for this marker.

                var orgtype = orgs[i].orgid.toString().split('').pop();
                var districttype = orgs[i].orgid.toString().split('')[0];
                orgs[i].classes = orgtypes[orgtype] + ' ' + districttypes[districttype];

                var col = 'red';
                orgs[i].ignore = false;
                if (orgs[i].authenticated > 0 && orgs[i].authenticated < timeref && orgs[i].lpf != null) {
                    col = 'orange';
                    counts.both++;
                    orgs[i].classes += ' both';
                    orgs[i].layer = 2;
                    orgs[i].url = URL.relativeUrl('/local/eduvidual/pages/myorgs.php?orgid=' + orgs[i].orgid);
                } else if(orgs[i].authenticated > 0 && orgs[i].authenticated < timeref) {
                    col = 'green';
                    counts.eduv++;
                    orgs[i].classes += ' eduv';
                    orgs[i].layer = 3;
                    orgs[i].url = URL.relativeUrl('/local/eduvidual/pages/myorgs.php?orgid=' + orgs[i].orgid);
                } else if(includenonegroup == 1) {
                    col = 'lightgray';
                    counts.none++;
                    orgs[i].classes += ' none invisible';
                    orgs[i].layer = 0;
                    orgs[i].url = '';
                } else {
                    orgs[i].ignore = true;
                }
                if (col != 'lightgray' && orgtype > 0) {
                    if (orgs[i].lon < smallest_lon) smallest_lon = orgs[i].lon;
                    if (orgs[i].lon > biggest_lon) biggest_lon = orgs[i].lon;
                    if (orgs[i].lat < smallest_lat) smallest_lat = orgs[i].lat;
                    if (orgs[i].lat > biggest_lat) biggest_lat = orgs[i].lat;
                }
                orgs[i].marker = URL.relativeUrl('/local/eduvidual/pix/google-maps-pin-' + col + '.svg#' + orgs[i].classes + '#' + orgs[i].layer);
            });

            smallest_lat = 45.18189988240382;
            smallest_lon = 8.88805461218309;
            biggest_lat = 49.517950306694665;
            biggest_lon = 17.45739054968309;


            var bounds = [[smallest_lat, smallest_lon], [biggest_lat, biggest_lon ]];
            var center_lat = (smallest_lat + biggest_lat) / 2;
            var center_lon = (smallest_lon + biggest_lon) / 2;
            var center = [ center_lat, center_lon];
            if (ADMIN.debug_gps) console.log('Bounds', bounds);
            if (ADMIN.debug_gps) console.log('Center', center);

            var width = $('#' + uniqid + '-map').width();
            if (ADMIN.debug_gps) console.log('Width', width);
            if (ADMIN.debug_gps) console.log('Height', width / 4 * 3);
            $('#' + uniqid + '-map').css('height', width / 4 * 3 + 'px');

            var icon = L.icon({
                iconUrl: URL.relativeUrl('/local/eduvidual/pix/google-maps-pin-blue.svg'),
                iconRetinaUrl: URL.relativeUrl('/local/eduvidual/pix/google-maps-pin-blue.svg'),
                iconSize: [29, 24],
                iconAnchor: [9, 21],
                popupAnchor: [0, -14]
            });
            Object.keys(orgs).forEach(function(i) {
                if (ADMIN.refresh_identifier != refresh_identifier) return; // Immediately abort!
                if (orgs[i].ignore) return; // Go to next item in loop.
                var useIcon = icon;
                if (typeof orgs[i].marker !== 'undefined' && orgs[i].marker != '') {
                    useIcon = L.icon({
                        iconUrl: orgs[i].marker,
                        iconRetinaUrl: orgs[i].marker,
                        iconSize: [29, 24],
                        iconAnchor: [9, 21],
                        popupAnchor: [0, -14],
                    });
                }

                var marker = L.marker(
                    [orgs[i].lat, orgs[i].lon],
                    {icon: useIcon}
                ).bindPopup(
                    (typeof orgs[i].url !== 'undefined' && orgs[i].url != '')
                    ? '<a href="' + orgs[i].url + '" target="_blank">' + orgs[i].name + ' (' + i + ')</a>'
                    : orgs[i].name
                );

                marker.addTo(layers[orgs[i].layer]);
            });

            var todaystr = [
                timerefdate.getFullYear(),
                String(timerefdate.getMonth()+1).padStart(2, '0'),
                String(timerefdate.getDate()).padStart(2, '0')
            ].join('-');
            var mapRatio = 1/9*6; // Screenratio 9:6
            var mapboxUrl = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
            var mapboxAttribution = 'Visualisierung der Moodle-Schulen in Österreich | ' + todaystr + ' | <a href="https://www.lernmanagement.at" target="_blank">Zentrum für Lernmanagement</a> | powered by &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';

            ADMIN.map = L.map(uniqid + '-map', {
                boxZoom:  true,
                center: center,
                layers: layers,
                zoom: 14,
                zoomControl: false,
                zoomSnap: 0.5,
            });
            ADMIN.map.fitBounds(bounds, { maxZoom: 18 });
            ADMIN.map.on('moveend', function(ev) {
                ADMIN.org_gps_bounds(uniqid);
            });
            ADMIN.map.on('resize', function(ev) {
                if (ADMIN.debug_gps) console.log('Map was resized', ev);
                var width = $('#' + uniqid + '-map').width();
                if (ADMIN.debug_gps) console.log('Width', width);
                if (ADMIN.debug_gps) console.log('Height', width * mapRatio);
                $('#' + uniqid + '-map').css('height', width * mapRatio + 'px');
            });

            var baseLayers = {
                'normal': L.tileLayer( mapboxUrl, {
                    attribution: mapboxAttribution,
                    id: 'mapbox.normal',
                    subdomains: ['a','b','c']
                })
            };
            baseLayers['normal'].addTo( ADMIN.map );

            L.control.zoom({
                 position:'bottomright'
            }).addTo(ADMIN.map);

            // Now we append classes to all markers
            $('#' + uniqid + ' .leaflet-marker-pane img').each(function(i,e) {
                if (ADMIN.refresh_identifier != refresh_identifier) return; // Immediately abort!
                var iconurl = $(e).attr('src');
                var tmp = iconurl.split('#');
                if (tmp.length == 3) {
                    $(e).addClass(tmp[1].replace(',', ' '));
                    $(e).css('z-index', 990 + tmp[2]);
                }
            });

            $('#' + uniqid + '-map').css('height', $('#' + uniqid + '-map').width() * mapRatio + 'px');

            ADMIN.org_gps_bounds(uniqid);
            ADMIN.org_gps_refresh(uniqid);
        },
        /**
         * Refresh current stats by reloading orgs in a specific rectangle.
         * @param uniqid of mustache used.
         */
        org_gps_refresh: function(uniqid) {
            var ADMIN = this;
            ADMIN.refresh_identifier++;
            var refresh_identifier = ADMIN.refresh_identifier;

            console.log('ADMIN.org_gps_refresh(uniqid)', uniqid);
            var map = $('#' + uniqid + '-map');

            // Go through all markers and flag if they are currently in bounds of map.
            $('#' + uniqid + '-map .leaflet-marker-icon').each(function(i,e) {
                if (ADMIN.refresh_identifier != refresh_identifier) return; // Immediately abort!
                var matrix = e.style.transform.split(/\w+\(|\);?/);
                if (matrix.length == 3) {
                    var coords = matrix[1].split('px').join('').split(' ').join('').split(',');
                }
                if (coords.length == 3) {
                    if (coords[0] < 0 || coords[0] > map.width() || coords[1] < 0 || coords[1] > map.height()) {
                        $(e).addClass('out-of-bounds');
                    } else {
                        $(e).removeClass('out-of-bounds');
                    }
                }
            });

            // Now flag all markers as visible.
            $('#' + uniqid + '-map .leaflet-marker-icon').addClass('flag-visible');
            // Now go through all filters. If a filter is not checked unflag markers of that filter to be visible.
            $('#' + uniqid + '-legend .trigger').each(function(i,e){
                if (ADMIN.refresh_identifier != refresh_identifier) return; // Immediately abort!
                var triggerkey = $(e).attr('data-trigger-key');
                if (typeof triggerkey !== 'undefined' && triggerkey !== '') {
                    var checked = $(e).prop('checked');
                    if (!checked) {
                        $('#' + uniqid + '-map .leaflet-marker-icon.' + triggerkey).removeClass('flag-visible');
                    }
                }
            });
            // Now toggle visibility as required.
            $('#' + uniqid + '-map .leaflet-marker-icon:not(.flag-visible)').addClass('invisible');
            $('#' + uniqid + '-map .leaflet-marker-icon.flag-visible').removeClass('invisible');

            //var countinvisibles = $('#' + uniqid + '-count-invisibles').prop('checked') ? '' : ',.invisible';
            var countinvisibles = $('#' + uniqid + '-count-invisibles').prop('checked');
            // Now go through all triggers and count items.
            var sums = {};
            $('#' + uniqid + '-legend .trigger').each(function(i,e){
                if (ADMIN.refresh_identifier != refresh_identifier) return; // Immediately abort!
                var triggerkey = $(e).attr('data-trigger-key');
                var filterid = $(e).attr('data-filterid');

                if (typeof triggerkey !== 'undefined' && triggerkey !== '') {
                    var checked = $(e).prop('checked');
                    var amount = 0;
                    var inboundmarkers = $('#' + uniqid + '-map .leaflet-marker-icon.' + triggerkey + ':not(.out-of-bounds)');
                    if (countinvisibles) {
                        // Count all that are in bounds.
                        amount = inboundmarkers.length;
                    } else if (checked) {
                        // Count only visibles
                        amount = inboundmarkers.not('.invisible').length;
                    }
                    $('#' + uniqid + '-legend td.' + triggerkey).html(amount);

                    if (typeof filterid !== 'undefined' && filterid != '') {
                        if (typeof sums[filterid] === 'undefined') sums[filterid] = 0;
                        sums[filterid] += amount;
                    }
                }
            });
            // Set sums.
            Object.keys(sums).forEach(function(filterid) {
                $('#' + uniqid + '-legend table.' + filterid + ' td.sum').html(sums[filterid]);
            });
        },
        questioncategories: function(uniqid, catid) {
            if (this.debug > 0) console.log('ADMIN.questioncategories(uniqid, catid)', uniqid, catid);
            var sender = $('.questioncategory-' + uniqid + '-' + catid);
            var questioncategories = new Array();
            var supportcourses = new Array();
            $.each($('.questioncategories-' + uniqid + ':checked'), function() {
                var catid = $(this).val();
                questioncategories.push(catid);
                supportcourses.push($('.supportcourses-' + uniqid + '-' + catid).val());
            });
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'admin', act: 'questioncategories', questioncategories: questioncategories, supportcourses: supportcourses }, { signalItem: $(sender) });
            });
        },
        /**
        * Sets the default role of teachers and students
        * @param type 'teacher' or 'student'
        * @param role roleid to set
        **/
        requireCapability: function(val) {
            if (this.debug > 0) console.log('ADMIN.requireCapability(val)', val);
            require(['local_eduvidual/main'], function(MAIN) {
                MAIN.connect({ module: 'admin', act: 'requirecapability', requirecapability: val }, { signalItem: $('#local_eduvidual_admin_requirecapability') });
            });
        },
        result: function(o) {
            if (o.data.act == 'blockfooter') {
                $('#local_eduvidual_footer').html(o.data.blockfooter);
            }
            if (o.data.act == 'coursebasements') {p
                // Refresh page to enforce a reload of basements in selectmenu
                history.go(0);
            }
            if (o.data.act == 'manageorgs_search') {
                if (typeof o.result.status !== 'undefined' && o.result.status == 'ok') {
                    var ul = $('#' + o.payload.uniqid + '-list').empty();
                    this.cache_orgs = o.result.orgs;
                    if (o.result.orgs.length === 0) {
                        ul.append($('<li>').html('No results'));
                    } else {
                        var orgids = Object.keys(o.result.orgs);
                        for (var a = 0; a < orgids.length; a++) {
                            var org = o.result.orgs[orgids[a]];
                            ul.append(
                                $('<li>').append(
                                    $('<a>')
                                      .html(org.name + ' (' + org.orgid + ')')
                                      .attr('href', '#')
                                      .attr('onclick', "require(['local_eduvidual/admin'], function(ADMIN) { ADMIN.manageorgs_open('" + o.payload.uniqid + "', " + org.id + "); });")
                                )
                            );
                        }
                    }
                }
            }
            if (o.data.act == 'manageorgs_store') {
                if (typeof o.result.errors !== 'undefined' && o.result.errors.length > 0) {
                    for (var a = 0; a < o.result.errors.length; a++) {
                        var error = o.result.errors[a];
                        $('#' + o.payload.uniqid + '-field-' + error).addClass('local_eduvidual_signal_error');
                    }
                }
            }
        },
        /**
         * Toggles the visibility of a column in admin_stats.
         * @param uniqid of template.
         * @param sender sending checkbox with attr data-type.
         */
        statsSwitchColumn: function(uniqid, sender) {
            console.log('ADMIN.statsSwitchColumn(uniqid, sender)', uniqid, sender);
            var type = $(sender).attr('data-type');
            var el = $('.' + uniqid + '-' + type);
            var settostate = ($(sender).prop('checked')) ? 'block' : 'none';
            //console.log(' => Set visibility of .' + uniqid + '-type to ' + settostate + ' as checked is ' + $(sender).prop('checked'));
            el.css('display', settostate);
        },
    };
});
