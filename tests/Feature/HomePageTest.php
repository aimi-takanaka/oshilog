<?php

namespace Tests\Feature;

use Tests\TestCase;

class HomePageTest extends TestCase
{
    public function test_home_page_shows_sales_points_and_how_to_use(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('RECORD YOUR OSHIKATSU DAYS.')
            ->assertSee('推し活の思い出を、まるごと記録。')
            ->assertSee('行った場所')
            ->assertSee('メンカラにチェンジ！')
            ->assertSee('会場を登録する')
            ->assertSee('公演を記録する')
            ->assertSee('使い方を見る')
            ->assertSee('使い方')
            ->assertSee('メンカラ')
            ->assertSee('ログイン')
            ->assertSee('はじめる');
    }

    public function test_root_path_is_the_home_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('推し活の思い出を、まるごと記録。');
    }

    public function test_member_color_picker_is_on_the_home_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('setOshiLogMemberColor')
            ->assertSeeInOrder([
                'data-member-color-swatch="blue"',
                'data-member-color-swatch="purple"',
                'data-member-color-swatch="pink"',
                'data-member-color-swatch="green"',
                'data-member-color-swatch="red"',
                'data-member-color-swatch="yellow"',
                'data-member-color-swatch="orange"',
                'data-member-color-swatch="white"',
            ])
            ->assertSeeInOrder([
                'home-member-notebook-blue',
                'home-member-notebook-purple',
                'home-member-notebook-pink',
                'home-member-notebook-green',
                'home-member-notebook-red',
                'home-member-notebook-yellow',
                'home-member-notebook-orange',
                'home-member-notebook-white',
            ])
            ->assertSee('oshilog-member-color');
    }

    public function test_mobile_header_opens_the_app_menu(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('comic-mobile-header', false)
            ->assertSee('トップ')
            ->assertSee('公演一覧')
            ->assertSee('会場一覧')
            ->assertSee('定番会場')
            ->assertSee('ログイン');
    }
}
