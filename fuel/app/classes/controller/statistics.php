<?php
class Controller_Statistics extends Controller_Template
{
	public function action_index()
	{
		$this->template->title = '統計';
		$this->template->breadcrumbs = [
			'/statistics' => '統計',
		];
		$this->template->content = View::forge('statistics/index');
	}

	public function action_cardsuses()
	{
		$this->template->title = 'カード使用・勝利回数';
		$this->template->breadcrumbs = [
			'/statistics' => '統計',
			'/statistics/cardsuses' => $this->template->title,
		];
		$this->template->content = View::forge('statistics/cardsuses');
		Asset::js(['statistics_cardsuses.js'], [], 'add_js');

		$types = ['occupation', 'minor_improvement'];
		$this->template->content->uses_ranking = [];
		$this->template->content->wins_ranking = [];
		foreach ($types as $type) {
			$this->template->content->uses_ranking[$type] = Model_GamesCards::get_uses_ranking($type, 2);
			$this->template->content->wins_ranking[$type] = Model_GamesCards::get_wins_ranking($type, 2);
		}
	}

	public function action_score()
	{
		$this->template->title = 'ハイスコア';
		$this->template->breadcrumbs = [
			'/statistics' => '統計',
			'/statistics/score' => $this->template->title,
		];
		$this->template->content = View::forge('statistics/score');
		Asset::js(['statistics_score.js'], [], 'add_js');

		$this->template->content->normal_ranking = Model_GamesScores::get_score_ranking(2, 0);
		$this->template->content->moor_ranking = Model_GamesScores::get_score_ranking(2, 1);
	}
}