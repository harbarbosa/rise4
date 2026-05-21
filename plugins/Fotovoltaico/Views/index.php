<div class="page-title clearfix">
    <h1><?php echo esc($page_title); ?></h1>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-off mb20"><?php echo esc($page_description); ?></p>

        <div class="row">
            <?php foreach ($available_modules as $card => $url) { ?>
                <div class="col-md-4 col-lg-3 mb15">
                    <a href="<?php echo get_uri($url); ?>" class="white-link">
                        <div class="card <?php echo $active_module === $card ? 'bg-light' : ''; ?>">
                            <div class="card-body">
                                <strong><?php echo app_lang('fotovoltaico_' . $card); ?></strong>
                            </div>
                        </div>
                    </a>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
