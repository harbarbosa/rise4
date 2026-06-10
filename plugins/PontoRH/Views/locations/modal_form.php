<?php
$model_info = $model_info ?? (object) array();
$google_maps_api_key = trim((string) ($google_maps_api_key ?? ''));

echo form_open(get_uri('pontorh/locais/save'), array('id' => 'pontorh-location-form', 'class' => 'general-form', 'role' => 'form'));
?>
<div class="modal-body clearfix">
    <?php echo form_hidden('id', (string) ($model_info->id ?? '')); ?>
    <div class="container-fluid">
        <div class="form-group">
            <div class="row">
                <label for="location_search" class="col-md-3"><?php echo app_lang('search'); ?></label>
                <div class="col-md-9">
                    <div class="input-group">
                        <input type="text" name="location_search" id="location_search" class="form-control" autocomplete="off" placeholder="<?php echo app_lang('search'); ?>" />
                        <button type="button" class="btn btn-default" id="pontorh-search-location"><?php echo app_lang('search'); ?></button>
                    </div>
                    <?php if ($google_maps_api_key) { ?>
                        <small class="text-muted"><?php echo app_lang('pontorh_google_maps_api_key'); ?></small>
                    <?php } else { ?>
                        <small class="text-muted">Configure a chave do Google Maps em Ponto RH > Configurações para habilitar sugestões automáticas.</small>
                    <?php } ?>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="name" class="col-md-3"><?php echo app_lang('name'); ?></label>
                <div class="col-md-9">
                    <input type="text" name="name" id="name" class="form-control" value="<?php echo esc($model_info->name ?? ''); ?>" autocomplete="off" required autofocus />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="address" class="col-md-3"><?php echo app_lang('address'); ?></label>
                <div class="col-md-9">
                    <textarea name="address" id="address" class="form-control" rows="3"><?php echo esc($model_info->address ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="latitude" class="col-md-3">Latitude</label>
                <div class="col-md-9">
                    <input type="text" name="latitude" id="latitude" class="form-control" value="<?php echo esc((string) ($model_info->latitude ?? '')); ?>" autocomplete="off" />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="longitude" class="col-md-3">Longitude</label>
                <div class="col-md-9">
                    <input type="text" name="longitude" id="longitude" class="form-control" value="<?php echo esc((string) ($model_info->longitude ?? '')); ?>" autocomplete="off" />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <label for="radius_meters" class="col-md-3"><?php echo app_lang('pontorh_allowed_radius_meters'); ?></label>
                <div class="col-md-9">
                    <input type="number" name="radius_meters" id="radius_meters" class="form-control" value="<?php echo esc((string) ($model_info->radius_meters ?? 200)); ?>" min="0" step="1" />
                </div>
            </div>
        </div>

        <div class="form-group">
            <div class="row">
                <div class="col-md-3"></div>
                <div class="col-md-9">
                    <div class="form-check mt10">
                        <?php echo form_checkbox('active', '1', !empty($model_info->active), "class='form-check-input' id='pontorh-location-active'"); ?>
                        <label for="pontorh-location-active" class="form-check-label"><?php echo app_lang('active'); ?></label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal"><?php echo app_lang('close'); ?></button>
    <button type="submit" class="btn btn-primary"><?php echo app_lang('save'); ?></button>
</div>
<?php echo form_close(); ?>

<script type="text/javascript">
    $(document).ready(function () {
        $("#pontorh-location-form").appForm({
            onSuccess: function () {
                $("#pontorh-locations-table").appTable({reload: true});
            }
        });

        <?php if ($google_maps_api_key) { ?>
        var pontorhGoogleGeocoder = null;
        var pontorhGoogleMapsInit = function () {
            var searchInput = document.getElementById('location_search');
            if (!searchInput || !window.google || !google.maps || !google.maps.places) {
                return;
            }

            pontorhGoogleGeocoder = new google.maps.Geocoder();
            var autocomplete = new google.maps.places.Autocomplete(searchInput, {
                fields: ['geometry', 'formatted_address', 'name']
            });

            autocomplete.addListener('place_changed', function () {
                var place = autocomplete.getPlace();
                if (!place) {
                    return;
                }

                if (place.formatted_address) {
                    $("#address").val(place.formatted_address);
                } else if (place.name) {
                    $("#address").val(place.name);
                }

                if (place.geometry && place.geometry.location) {
                    $("#latitude").val(place.geometry.location.lat());
                    $("#longitude").val(place.geometry.location.lng());
                }
            });

            var searchAddress = function () {
                var query = (searchInput.value || '').trim();
                if (!query || !pontorhGoogleGeocoder) {
                    return;
                }

                pontorhGoogleGeocoder.geocode({ address: query }, function (results, status) {
                    if (status !== 'OK' || !results || !results.length) {
                        return;
                    }

                    var place = results[0];
                    if (place.formatted_address) {
                        $("#address").val(place.formatted_address);
                    } else {
                        $("#address").val(query);
                    }

                    if (place.geometry && place.geometry.location) {
                        $("#latitude").val(place.geometry.location.lat());
                        $("#longitude").val(place.geometry.location.lng());
                    }
                });
            };

            $("#pontorh-search-location").off('click').on('click', searchAddress);
            $("#location_search").off('keypress').on('keypress', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    searchAddress();
                }
            });
        };

        if (window.google && google.maps && google.maps.places) {
            pontorhGoogleMapsInit();
        } else if (!window.pontorhGoogleMapsScriptLoaded) {
            window.pontorhGoogleMapsScriptLoaded = true;
            var script = document.createElement('script');
            script.src = "https://maps.googleapis.com/maps/api/js?key=<?php echo esc($google_maps_api_key); ?>&libraries=places&language=<?php echo esc(app_lang('language_locale')); ?>";
            script.async = true;
            script.defer = true;
            script.onload = pontorhGoogleMapsInit;
            document.head.appendChild(script);
        }
        <?php } ?>
    });
</script>
