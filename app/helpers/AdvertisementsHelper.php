<?php
class AdvertisementsHelper extends Rails\ActionView\Helper
{
    public function print_advertisement($ad_type)
    {
        if (CONFIG()->can_see_ads(current_user())) {
            // $ad = Advertisement::random($ad_type);
            // if ($ad)
                // return $this->contentTag("div", $this->linkTo($this->imageTag($ad->image_url, array('alt' => "Advertisement", 'width' => $ad->width, 'height' => $ad->height), redirect_advertisement_path($ad)), 'style' => "margin-bottom: 1em;"));
        }
    }
}