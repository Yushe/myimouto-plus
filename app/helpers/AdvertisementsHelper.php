<?php
class AdvertisementsHelper extends Rails\ActionView\Helper
{
    public function print_advertisement($ad_type)
    {
        if (CONFIG()->can_see_ads(current_user())) {
            $ad = Advertisement::random($ad_type);
            if ($ad) {
                if ($ad->html) {
                    return $ad->html;
                } else {
                    return $this->linkTo(
                        $this->imageTag(
                            $ad->image_url,
                            ['alt' => "Advertisement", 'width' => $ad->width, 'height' => $ad->height]
                        ),
                        $this->redirectAdvertisementPath($ad),
                        ['target' => '_blank']
                    );
                }
            }
        }
    }
}
