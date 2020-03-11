<?php

    namespace App\Http\Controllers\Signatures;

    use App\Utilities\ColourHelper;
    use App\Utilities\MinecraftAvatar\ThreeDAvatar;
    use Illuminate\Http\Request;
    use Illuminate\Http\Response;
    use Image;
    use Plancke\HypixelPHP\classes\gameType\GameTypes;
    use Plancke\HypixelPHP\exceptions\HypixelPHPException;
    use Plancke\HypixelPHP\responses\player\Player;

    /**
     * Class GeneralSignatureController
     *
     * @package App\Http\Controllers\Signatures
     */
    class GeneralSignatureController extends BaseSignature {

        /**
         * @param $image
         *
         * @return array
         */
        protected static function getColours($image): array {
            $black  = imagecolorallocate($image, 0, 0, 0);
            $purple = imagecolorallocate($image, 204, 0, 204);
            $blue   = imagecolorallocate($image, 0, 204, 204);
            return [$black, $purple, $blue];
        }

        /**
         * @param Request $request
         * @param Player  $player
         *
         * @return Response
         * @throws HypixelPHPException
         */
        protected function signature(Request $request, Player $player): Response {
            $image = BaseSignature::getImage(740, 160);
            [$black, $purple, $blue] = self::getColours($image);
            $fontSourceSansProLight = resource_path('fonts/SourceSansPro/SourceSansPro-Light.otf');

            $karma          = $player->get('karma', 0);
            $vanityTokens   = $player->get('vanityTokens', 0);
            $mostRecentGame = $player->get('mostRecentGameType', 'None');
            $username       = $player->getName();

            $rank       = $player->getRank(false);
            $rankColour = $rank->getColor();
            $rankName   = $rank->getCleanName();
            if ($rankName === 'DEFAULT') {
                $rankName = 'Player';
            }
            $rankNameWithColour = $rankColour . $rankName;

            $lastgameType = GameTypes::fromEnum($mostRecentGame);
            if ($lastgameType !== null) {
                $mostRecentGame = $lastgameType->getName();
            }

            if ($request->has('no_3d_avatar')) {
                $avatarWidth        = 0;
                $textX              = $avatarWidth + 5;
                $textBeneathAvatarX = $textX;
            } else {
                $threedAvatar = new ThreeDAvatar();
                $avatarImage  = $threedAvatar->getThreeDSkinFromCache($player->getUUID(), 4, 30, false, true, true);

                $avatarWidth        = imagesx($avatarImage);
                $textX              = $avatarWidth + 5;
                $textBeneathAvatarX = $textX;

                imagecopy($image, $avatarImage, 0, 0, 0, 0, imagesx($avatarImage), imagesy($avatarImage));
                imagedestroy($avatarImage);
            }

            if ($request->has('guildTag')) {
                $guildTag = '§7[' . $player->getGuildTag() . ']';
                if ($guildTag === '§7[]') {
                    $guildTag = '§7[-]';
                }
                ColourHelper::minecraftStringToTTFText($image, $fontSourceSansProLight, 25, $textX, 14, '§0' . $username . ' ' . $guildTag);
            } else {
                imagettftext($image, 25, 0, $textX, 30, $black, $fontSourceSansProLight, $username);
            }

            $linesY = [60, 95, 130]; // Y starting points of the various text lines

            ColourHelper::minecraftStringToTTFText($image, $fontSourceSansProLight, 20, $textX, 44, $rankNameWithColour); // Rank name (coloured)

            imagettftext($image, 20, 0, $textX, $linesY[1], $purple, $fontSourceSansProLight, $karma . ' karma'); // Amount of karma

            imagettftext($image, 20, 0, 380, $linesY[0], $black, $fontSourceSansProLight, 'Level ' . $player->getLevel()); // Network level

            imagettftext($image, 20, 0, 380, $linesY[1], $black, $fontSourceSansProLight, 'Daily Reward High Score: ' . $player->getInt('rewardHighScore')); // Daily reward high score

            imagettftext($image, 20, 0, $textBeneathAvatarX, $linesY[2], $blue, $fontSourceSansProLight, $vanityTokens . ' Hypixel Credits'); // Hypixel Credits

            imagettftext($image, 20, 0, 380, $linesY[2], $black, $fontSourceSansProLight, 'Recently played: ' . $mostRecentGame); // Last game played

            $this->addWatermark($image, $fontSourceSansProLight, 740, 160); // Watermark/advertisement

            return Image::make($image)->response('png');
        }

    }
