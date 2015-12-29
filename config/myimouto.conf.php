<?php

use MyImouto\User;
use MyImouto\Post;

return [
    # The name of this booru.
    'app_name' => 'my.imouto',
    
    # Host name. Must not include scheme (i.e. http(s)://) nor trailing slash.
    // 'server_host' => '127.0.0.1:3000',
    'server_host' => 'myimouto.p7',
    
    # This is the same as $server_host but includes scheme.
    'url_base' => 'http://myimouto.p7',
    
    # The version of this MyImouto
    'version' => '2.0.0',
    
    # This is a salt used to make dictionary attacks on account passwords harder.
    'user_password_salt' => 'choujin-steiner',
    
    # Set to true to allow new account signups.
    'enable_signups' => true,
    
    # Newly created users start at this level. Set this to 30 if you want everyone
    # to start out as a privileged member.
    'starting_level' => 30,
    
    # What method to use to store images.
    # local_flat: Store every image in one directory.
    # local_hierarchy: Store every image in a hierarchical directory, based on the post's MD5 hash. On some file systems this may be faster.
    # local_flat_with_amazon_s3_backup: Store every image in a flat directory, but also save to an Amazon S3 account for backup.
    # amazon_s3: Save files to an Amazon S3 account.
    # remote_hierarchy: Some images will be stored on separate image servers using a hierarchical directory.
    'image_store' => 'local_hierarchy',
    
    # Set to true to enable downloading whole pools as ZIPs.
    'pool_zips' => true,
    
    'max_preview_width' => 300,
    'max_preview_height' => 300,
    'preview_quality' => 85,
    
    # Enables image samples for large images.
    'image_samples' => true,
    
    # The maximum dimensions and JPEG quality of sample images.    This is applied
    # before sample_max/sample_min below.    If sample_width is nil, neither of these
    # will be applied and only sample_min/sample_max below will determine the sample
    # size.
    'sample_width' => null,
    'sample_height' => 1000, # Set to null if you never want to scale an image to fit on the screen vertically
    'sample_quality' => 92,
    
    # The greater dimension of sample images will be clamped to sample_min, and the smaller
    # to sample_min.    2000x1400 will clamp a landscape image to 2000x1400, or a portrait
    # image to 1400x2000.
    'sample_max' => 1500,
    'sample_min' => 1200,
    
    # The maximum dimensions of inline images for the forums and wiki.
    'inline_sample_width' => 800,
    'inline_sample_height' => 600,
    
    # Resample the image only if the image is larger than sample_ratio * sample_dimensions.
    # This is ignored for PNGs, so a JPEG sample is always created.
    'sample_ratio' => 1,
    
    # A prefix to prepend to sample files
    'sample_filename_prefix' => '',
    
    /**
     * Configure how PNG files will be processed.
     *
     * Allowed values:
     *
     * * keep:    save the PNG file.
     * * discard: convert to JPG.
     * * sample:  keep PNG but create a JPG version.
     *
     * NOTE: this "PNG sample" is different from the smaller
     * image sample (see image_samples option). This option
     * exists for downloading purposes.
     *
     * NOTE: options to configure the PNG sample are below.
     */
    'png_process' => 'keep',
    
    # Scale PNG samples to fit in these dimensions.
    'jpeg_width' => 3500,
    'jpeg_height' => 3500,
    
    /**
     * Sample ratio when sampling a PNG.
     */
    'png_to_jpg_ratio' => 1,
    
    # Resample the image only if the image is larger than jpeg_ratio * jpeg_dimensions.    If
    # not, PNGs can still have a JPEG generated, but no resampling will be done.
    'jpeg_ratio' => 1.25,
    'jpeg_quality' => 95,
    
    # If enabled, URLs will be of the form:
    # http://host/image/00112233445566778899aabbccddeeff/12345 tag tag2 tag3.jpg
    #
    # This allows images to be saved with a useful filename, and hides the MD5 hierarchy (if
    # any).    This does not break old links, links to the old URLs are still valid.    This
    # requires URL rewriting (not redirection!) in your webserver.
    'use_pretty_image_urls' => false,
    
    # If use_pretty_image_urls is true, sets a prefix to prepend to all filenames.    This
    # is only present in the generated URL, and is useful to allow your downloaded files
    # to be distinguished from other sites, for example, "moe 12345 tags.jpg" vs.
    # "kc 54321 tags.jpg".
    'download_filename_prefix' => "myimouto",
    
    # Files over this size will always generate a sample, even if already within
    # the above dimensions.
    'sample_always_generate_size' => 524288, // 512*1024
    
    # After a post receives this many posts, new comments will no longer bump the post in comment/index.
    'comment_threshold' => 9999,
    
    /**
     * Contraint users with level <= `user_level` to upload
     * `max` posts per day. Set `max` to 0 to disable this.
     */
    'upload_limit' => [
        'max' => 16,
        'user_level' => User::LEVEL_MEMBER
    ],
    
    # This sets the minimum and maximum value a user can record as a vote.
    'vote_record_min' => 0,
    'vote_record_max' => 3,
    
    # This allows posts to have parent-child relationships. However, this requires manually updating the post counts stored in table_data by periodically running the script/maintenance script.
    'enable_parent_posts' => true,
    
    # Show only the first page of post/index to visitors.
    'show_only_first_page' => false,
    
    # Defines the various user levels.
    'user_levels' => [
        "Unactivated" => 0,
        "Blocked"     => 10,
        "Member"      => 20,
        "Privileged"  => 30,
        "Contributor" => 33,
        "Janitor"     => 35,
        "Mod"         => 40,
        "Admin"       => 50
    ],
    
    # Defines the various tag types. You can also define shortcuts.
    'tag_types' => [
        "General"   => 0,
        "general"   => 0,
        "Artist"    => 1,
        "artist"    => 1,
        "art"       => 1,
        "Copyright" => 3,
        "copyright" => 3,
        "copy"      => 3,
        "Character" => 4,
        "character" => 4,
        "char"      => 4,
        "Circle"    => 5,
        "circle"    => 5,
        "cir"       => 5,
        "Faults"    => 6,
        "faults"    => 6,
        "fault"     => 6,
        "flt"       => 6
    ],
    
    # Tag type ordering in various locations.
    'tag_order' => [
        'circle',
        'artist',
        'copyright',
        'character',
        'general'
    ],
    
    'default_tag' => 'tagme',
    
    'default_tag_type' => 0,
    
    # Tag type IDs to not list in recent tag summaries, such as on the side of post/index:
    'exclude_from_tag_sidebar' => array(0, 6),
    
    # Defines the default blacklists for new users.
    'default_blacklists' => array (
        "rating:q",
        "rating:e"
    ),
    
    # Enable the artists interface.
    'enable_artists' => true,
    
    # Users cannot search for more than X regular tags at a time.
    'tag_query_limit' => 6,
    
    # Set this to true to hand off time consuming tasks (downloading files, resizing images, any sort of heavy calculation) to a separate process.
    # @see $active_job_tasks
    # @see is_job_task_active()
    'enable_asynchronous_tasks' => false,
    
    'avatar_max_width' => 125,
    'avatar_max_height' => 125,
    
    # The number of posts a privileged_or_lower can have pending at one time.    Any
    # further posts will be rejected.
    'max_pending_images' => null,
    
    # If set, posts by privileged_or_lower accounts below this size will be set to
    # pending.
    'min_mpixels' => null,
    
    # If true, pending posts act like hidden posts: they're hidden from the index unless
    # pending:all is used, and posts are bumped to the front of the index when they're
    # approved.
    'hide_pending_posts' => true,
    
    # The image service name of this host, if any.
    'local_image_service' => "",

    # List of image services available for similar image searching.
    'image_service_list' => [
        "danbooru.donmai.us" => "http://iqdb.yande.re/index.xml",
        "yande.re"           => "http://iqdb.yande.re/index.xml",
        "konachan.com"       => "http://iqdb.yande.re/index.xml"
    ],
    
    'dupe_check_on_upload' => false,
    
    # Members cannot post more than X comments in an hour.
    'member_comment_limit' => 20,
    
    # (Next 2 arrays will be filled when including config/languages.php)
    'language_names' => [],
    
    # Languages that we're aware of.    This is what we show in "Secondary languages", to let users
    # select which languages they understand and that shouldn't be translated.
    'known_languages' => [],
    
    # Languages that we support translating to.    We'll translate each comment into all of these
    # languages.    Set this to array() to disable translation.
    'translate_languages' => [], // array('en', 'ja', 'zh-CN', 'zh-TW', 'es'):
    
    'available_locales' => ['de', 'en', 'es', 'ja', 'ru', 'zh_CN'],
    
    # The default name to use for anyone who isn't logged in.
    'default_guest_name' => "Anonymous",
    
    'admin_contact' => 'admin@myimouto',
    
    # Background color when resizing transparent PNG/GIF images.
    # Using RGB values.
    # Default: [126, 126, 126] (gray)
    'bgcolor' => [126, 126, 126],
    
    # Default language.
    'default_locale' => 'en',
    
    # Use this config to enable Google Analytics. Fill in the GA Tracking ID (like 'UA-XXXXX-X')
    'ga_tracking_id' => '',
    
    # Max number of posts to cache
    'tag_subscription_post_limit' => 200,
    
    # Max number of fav tags per user
    'max_tag_subscriptions' => 5,
    
    /**
     * Automatically add tags to posts, for example add "gif" tag
     * to a gif image.
     */
    'enable_automatic_tags' => true,
    
    /**
     * *******************************
     * MyImouto-specific configuration
     * *******************************
     */
    
    # Default limit for /post
    'post_index_default_limit' => 16,
    
    # Default limit for /pool
    'pool_index_default_limit' => 20,
    
    # For /post tag left-sidebar, show tags of posts that were posted N days ago.
    # This value will be passed to strtotime(). Check out http://php.net/manual/en/function.strtotime.php
    # for more info.
    # The leading minus sign (-) will be added automatically, therefore must be omitted.
    'post_index_tags_limit' => '1 day',
    
    # Default rating for upload (e, q or s).
    'default_rating_upload' => 'q',
    
    # Default rating for import (e, q or s).
    'default_rating_import' => 'q',
    
    # Automatically add "gif" tag to GIF files.
    'add_gif_tag_to_gif' => true,
    
    # Automatically add "flash" tag to SWF files.
    'add_flash_tag_to_swf' => true,
    
    # Enables the E/R hotkeys to jump to the edit/reply forms respectevely in post#show.
    'post_show_hotkeys' => true,
    
    # Max number of dmails users can send in one hour.
    'max_dmails_per_hour' => 10,
    
    # Only Job tasks listed here will be active.
    # @see $enable_asynchronous_tasks
    # @see is_job_task_active()
    'active_job_tasks' => [
        'periodic_maintenance',
        'external_data_search',
        'upload_batch_posts',
        'approve_tag_implication',
        'approve_tag_alias',
        'calculate_tag_subscriptions'
    ],
    
    # Javascripts assets manifest files.
    'asset_javascripts' => [
        'application',
        'moe-legacy/application'
    ],
    
    # Stylesheets assets manifest files.
    'asset_stylesheets' => [
        'application'
    ],
    
    # Adds delete option to Post Mode menu in post#index, only available for admins.
    # Be careful with this.
    'delete_post_mode' => false,
    
    # When deleting a post, it will be deleted completely at once.
    # Be careful with this.
    'delete_posts_permanently' => false,
    
    # Enables post#search_external_data, available for any user.
    'enable_find_external_data' => true,
    
    # Enables manual tag deletion.
    'enable_tag_deletion' => true,
    
    # Enables manual tax fix count.
    'enable_tag_fix_count' => true,
    
    # Show homepage or redirect to /post otherwise.
    'skip_homepage' => false,
    
    # Show moe imoutos (post count) in homepage.
    'show_homepage_imoutos' => true,
    
    # Creates a fake sample_url for posts without a sample, so they can be zoomed-in in browse mode.
    # This is specifically useful if you're not creating image samples.
    'fake_sample_url' => true,
    
    # Parse moe imouto filenames upon post creation
    # These only work if filename is like "moe|yande.re 123 tag_1 tag_2" by default.
    # You can modify how the filenames are parsed in the
    # app/models/post/filename_parsing_methods.php file, the
    # _parse_filename_tags and _parse_filename_source methods.
    
    # Take tags from filename.
    'tags_from_filename' => true,
    
    # Automatically create source for images.
    'source_from_filename' => true,
    
    # Enable resizing image in post#show by double-clicking on it.
    'dblclick_resize_image' => true,
    
    # Enable news bar on the top of the page.
    'enable_news_ticker' => true,
    
    # Menu wiki link parameters.
    # By default it links to "help:home" wiki, but not all boorus will have a wiki called
    # like that. Set the value to false or null if you want to link to the wiki index instead.
    'menu_wiki_link' => ['title' => "help:home"],
    
    # By default, filenames in pool zips are named after the pool order of the post.
    # The following option will show an option in pool#show that will let users
    # keep pretty filenames instead (with id and tags, like in post#show).
    'allow_pool_zip_pretty_filenames' => true,
    
    # Makes the results in post#similar open in a new window.
    'similar_image_results_on_new_window' => true,
    
    # When clicking "Add translation" in post#show, instead of creating a note, a notice will
    # show with info on how to create a note using the new functionality.
    # Setting this to false will cause the link to create a note like before, but the new
    # functionality will stay.
    'disable_old_note_creation' => true,
    
    # Only users with level equal or higher than this can see posts.
    # Unless they have higher level, users won't be able to view posts or access the Post Browser,
    # download pool zips and also direct-links in post lists (post#index, pool#show) will be disabled for them.
    # It's recommended to combine this with anti-directlink rules in the .htaccess.
    'user_min_level_can_see_posts' => 0,
    
    # Enables tag autocomplete in various input fields.
    'enable_tag_completion' => true,
    
    # Enables tag autocomplete in the home page.
    # Requires $enable_tag_completion.
    'tag_completion_in_homepage' => false,
    
    # Configuration for External Data Search job task.
    # Only the needed values to be changed can be copied over the 
    # custom config class.
    'external_data_search_config' => [
        # List of server names to search data in.
        # The server names listed here are just a reference to the list in the
        # image_service_list option, i.e., the names listed here must also be
        # listed in image_service_list.
        'servers' => [
            "danbooru.donmai.us"
        ],
        
        # Interval between requests, in seconds.
        'interval' => 3,
        
        # True to grab post source from the first result with source.
        # Otherwise, source is not touched.
        'source' => true,
        
        # True to merge found tags with current tags.
        # False to replace current tags with found ones.
        'merge_tags' => true,
        
        # Max post updates per job call.
        # 0 = no limit = find data non-stop for all posts till end or error.
        'limit' => 100,
        
        # Automatically sets rating from the first similar post with rating (usually
        # the best match).
        'set_rating' => false,
        
        # Exclude these tags from result tags.
        'exclude_tags' => [],
        
        # Image similarity threshold. Images with lower similarity ratio
        # will be ignored.
        # Set to 0 for automatic threshold.
        'similarity' => 90
    ],
    
    /**
     * Active advertisements spots.
     * Remove one of them from the list and the ads in that
     * page/place won't show.
     * The ones commented are new, extra spots.
     */
    'active_ad_spots' => [
        'post#index-sidebar',
        'post#show-sidebar',
        'post#show-top',
        'post#index-top',
        // 'post#index-bottom',
        // 'post#show-bottom'
    ],
    
    # Timeout for Danbooru::http_get_streaming() (used by batch upload, similar images, etc.).
    # If this limit is reached, the file that's being downloaded will be invalid.
    # Set to 0 to wait indefinitely.
    'http_streaming_timeout' => 10,
    
    # Determine who can see a post.
    'can_see_post' => function(User $user, Post $post)
    {
        # By default, no posts are hidden.
        return true;

        # Some examples:
        #
        # Hide post if user isn't privileged and post is not safe:
        # if ($post->rating == 'e' && $user->is_privileged_or_higher()) return true,
        # 
        # Hide post if user isn't a mod and post has the loli tag:
        # if ($post->has_tag('loli') && $user->is_mod_or_higher()) return true,
    },

    # Determines who can see ads.
    'can_see_ads' => function($user)
    {
        return $user->level <= User::LEVEL_MEMBER;
    },

    # Just enabling tasks won't assure a certain task is active.
    # This will tell us if job tasks are enabled and if a specific task is active.
    'is_job_task_active' => function($name)
    {
        return conf('myimouto.conf.enable_asynchronous_tasks') && in_array($name, $this->active_job_tasks);
    },

    /**
     * Does a couple of checks:
     * - If $user can see ads.
     * - If $spot is active.
     *
     * @return bool
     */
    'can_show_ad' => function($spot, User $user)
    {
        return in_array($spot, $this->active_ad_spots) && can_see_ads($user);
    },

    'query_builder' => [
        'mysql' => MyImouto\Post\SqlQueryBuilder::class
    ]
    
    // /**
     // * Parses image filename into tags.
     // * Must return an array with tags or an empty value.
     // */
    // 'filename_to_tags' => function($filename)
    // {
        // if (preg_match("/^(?:yande\.re|moe) \d+ (.*)$/", $filename, $m)) {
            // return array_filter(array_unique(explode(' ', $m[1])));
        // }
    // },

    // /**
     // * Parses image filename into source.
     // * Must return a string or an empty value.
     // */
    // 'filename_to_source' => function($filename)
    // {
        // if (preg_match("/^(?:moe|yande\.re) (\d+) /", $filename, $m)) {
            // return 'https://yande.re/post/show/'.$m[1];
        // }
    // }
];
