<?php
class Model_GamesScores
{
	const TABLE_NAME = 'games_scores';

	/**
	 * GamesScoresレコード作成
	 * @param  string $game_id ゲームID
	 * @param  array  $players ユーザIDの配列(番手順)
	 */
	public static function create($game_id, $players)
	{
		$created_at = date('Y-m-d H:i:s');
		$query = DB::insert(self::TABLE_NAME)
					->columns([
						'game_id',
						'player_order',
						'username',
					]);
		foreach ($players as $key => $player) {
			$query->values([
				$game_id,
				$key + 1,
				$player,
			]);
		}
		$query->execute();
	}

	/**
	 * マイページ用に未編集戦績レコードを取得
	 * @param  string $username ユーザID
	 * @return aaray            GamesScoresレコードの配列
	 */
	public static function get_list_for_home($username)
	{
		$columns = [
			Model_Games::TABLE_NAME . '.game_id',
			'player_order',
			'username',
			'players_number',
			'regulation_name',
			'created_at',
			'is_moor',
			'owner',
			'created_at',
		];
		$query = DB::select_array($columns)
					->from(self::TABLE_NAME)
					->where('username', '=', $username)
					->and_where('fields', '=', null)
					->join(Model_Games::TABLE_NAME, 'inner')
					->on(self::TABLE_NAME . '.game_id', '=', Model_Games::TABLE_NAME . '.game_id')
					->join(Model_RegulationsMaster::TABLE_NAME, 'inner')
					->on(Model_RegulationsMaster::TABLE_NAME . '.regulation_type', '=', Model_Games::TABLE_NAME . '.regulation_type')
					->order_by('created_at', 'desc');
		return $query->execute()->as_array();
	}

	/**
	 * 戦績編集用にGamesScoresレコードを取得
	 * @param  string $game_id      ゲームID
	 * @param  int    $player_order 番手
	 * @return array                GamesScoresレコード
	 */
	public static function get_for_edit($game_id, $player_order)
	{
		$query = DB::select()
					->from(self::TABLE_NAME)
					->where(self::TABLE_NAME . '.game_id', '=', $game_id)
					->and_where('player_order', '=', $player_order)
					->join(Model_Games::TABLE_NAME, 'inner')
					->on(self::TABLE_NAME . '.game_id', '=', Model_Games::TABLE_NAME . '.game_id')
					->join(Model_RegulationsMaster::TABLE_NAME, 'inner')
					->on(Model_RegulationsMaster::TABLE_NAME . '.regulation_type', '=', Model_Games::TABLE_NAME . '.regulation_type')
					->limit(1);
		$records = $query->execute()->as_array();
		if ($records === []) {
			return [];
		}
		return $records[0];
	}

	/**
	 * 戦績更新(データはPOSTの値を利用)
	 * @param  string $game_id      ゲームID
	 * @param  int    $player_order 番手
	 * @param  string $image        盤面画像のパス
	 */
	public static function update($game_id, $player_order, $image)
	{
		$values = [];
		foreach (Controller_Games::BASIC_POINTS_LIST as $field => $label) {
			$values[$field] = Input::post($field);
		}
		if (Input::post('horses')) {
			$values['horses'] = Input::post('horses');
		}
		$fields = [
			'unused_spaces',
			'stable',
			'rooms',
			'family',
			'begging',
			'card_points',
			'bonus_points',
			'total_points',
			'rank',
			'comment'
		];
		foreach ($fields as $field) {
			$values[$field] = Input::post($field);
		}
		if ($image) {
			$values['image'] = $image;
		}

		$query = DB::update(self::TABLE_NAME)
					->set($values)
					->where('game_id', '=', $game_id)
					->and_where('player_order', '=', $player_order);
		$query->execute();
	}

	/**
	 * 戦績表示用にGamesScoresレコードを取得
	 * @param  string $game_id ゲームID
	 * @return array           GamesScoresレコードの配列
	 */
	public static function get_for_view($game_id)
	{
		$query = DB::select()
					->from(self::TABLE_NAME)
					->where('game_id', '=', $game_id)
					->order_by('player_order', 'asc')
					->join(Model_Users::TABLE_NAME, 'left outer')
					->on(self::TABLE_NAME . '.username', '=', Model_Users::TABLE_NAME . '.username');
		$records = $query->execute()->as_array();
		foreach ($records as &$record) {
			$record['profile_fields'] = unserialize($record['profile_fields']);
			if (! $record['profile_fields']) {
				$record['profile_fields'] = [];
			}
		}
		return array_column($records, null, 'player_order');
	}

	/**
	 * ゲーム削除
	 * @param  string $game_id ゲームID
	 */
	public static function delete($game_id)
	{
		$query = DB::delete(self::TABLE_NAME)
					->where('game_id', '=', $game_id);
		return $query->execute();
	}

	/**
	 * 順位を更新
	 * @param string $game_id   ゲームID
	 * @param array  $rank_list 順位の配列(番手順)
	 */
	public static function set_rank($game_id, $rank_list)
	{
		foreach ($rank_list as $order => $rank) {
			$query = DB::update(self::TABLE_NAME)
						->value('rank', $rank)
						->where('game_id', '=', $game_id)
						->and_where('player_order', '=', $order);
			$query->execute();
		}
	}

	/**
	 * メンバーごとのゲーム数を数える
	 * @param  string $username ユーザID
	 * @return int              指定したユーザが参加したゲーム数
	 */
	public static function count_list_for_users($username)
	{
		$query = DB::select(DB::expr('COUNT(*) AS count'))
					->from(self::TABLE_NAME)
					->where('username', '=', $username);
		return $query->execute()->as_array()[0]['count'] ?? 0;
	}

	/**
	 * メンバー詳細用にGamesScoresレコードのリストを取得
	 * @param  string     $username   ユーザID
	 * @param  Pagination $pagination
	 * @return array                  GamesScoresレコードの配列
	 */
	public static function get_list_for_users($username, $pagination)
	{
		$query = DB::select()
					->from(self::TABLE_NAME)
					->where(self::TABLE_NAME . '.username', '=', $username)
					->join(Model_Games::TABLE_NAME, 'inner')
					->on(self::TABLE_NAME . '.game_id', '=', Model_Games::TABLE_NAME . '.game_id')
					->join(Model_RegulationsMaster::TABLE_NAME, 'inner')
					->on(Model_RegulationsMaster::TABLE_NAME . '.regulation_type', '=', Model_Games::TABLE_NAME . '.regulation_type')
					->order_by('created_at', 'desc')
					->limit($pagination->per_page)
					->offset($pagination->offset);
		return $query->execute()->as_array();
	}

	/**
	 * すべての戦績データを取得(全ゲーム順位自動計算用)
	 * @return array game_id[] => GamesScoresレコード[]
	 */
	public static function get_all()
	{
		$query = DB::select()
					->from(self::TABLE_NAME);
		$records = $query->execute()->as_array();
		$list = [];
		foreach ($records as $record) {
			$list[$record['game_id']][] = $record;
		}
		return $list;
	}
}