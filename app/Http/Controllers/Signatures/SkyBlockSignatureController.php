<?php
    /**
 * Copyright (c) 2020 Max Korlaar
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *  Redistributions of source code must retain the above copyright notice, this
 *   list of conditions and the following disclaimer.
 *
 *  Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions, a visible attribution to the original author(s)
 *   of the software available to the public, and the following disclaimer
 *   in the documentation and/or other materials provided with the distribution.
 *
 *  Neither the name of the copyright holder nor the names of its
 *   contributors may be used to endorse or promote products derived from
 *   this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
 * FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
 * OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

    namespace App\Http\Controllers\Signatures;

    use App\Exceptions\HypixelFetchException;
    use App\Exceptions\SkyBlockEmptyProfileException;
    use App\Utilities\SkyBlock\SkyBlockStatsDataParser;
    use Illuminate\Http\Request;
    use Illuminate\Http\Response;
    use Image;
    use Plancke\HypixelPHP\classes\gameType\GameTypes;
    use Plancke\HypixelPHP\responses\player\GameStats;
    use Plancke\HypixelPHP\responses\player\Player;

    /**
     * Class SkyBlockSignatureController
     *
     * @package App\Http\Controllers\Signatures
     */
    class SkyBlockSignatureController extends BaseSignature {
        protected ?string $profileId;

        public function render(Request $request, string $uuid, string $profileId = null): Response {
            $this->profileId = $profileId;

            return parent::render($request, $uuid);
        }

        /**
         * @param Request $request
         * @param Player  $player
         *
         * @return Response
         */
        protected function signature(Request $request, Player $player): Response {
            $image                  = BaseSignature::getImage(650, 160);
            $fontSourceSansProLight = resource_path('fonts/SourceSansPro/SourceSansPro-Light.otf');

            if ($this->profileId === null) {
                $mainStats = $player->getStats();

                /** @var GameStats $stats */
                $stats    = $mainStats->getGameFromID(GameTypes::SKYBLOCK);
                $profiles = $stats->get('profiles', []);
                //dd($profiles);

                $firstProfile = array_shift($profiles);
                if ($firstProfile === null) {
                    return self::generateErrorImage('Player does not have any SkyBlock profiles on their account, or they may have disabled API access for SkyBlock.');
                }

                $this->profileId = $firstProfile['profile_id'];
            }

            try {
                $stats = SkyBlockStatsDataParser::getSkyBlockStats($player, $this->profileId);
            } catch (HypixelFetchException $exception) {
                return self::generateErrorImage('An error has occurred while trying to fetch this SkyBlock profile. Please try again later.');
            } catch (SkyBlockEmptyProfileException $e) {
                return self::generateErrorImage('This SkyBlock profile has no data. It may have been deleted.');
            }

            //dd($stats);
            //            dd($stats);

            $this->addWatermark($image, $fontSourceSansProLight, 650, 160); // Watermark/advertisement

            return Image::make($image)->response('png');
        }


    }