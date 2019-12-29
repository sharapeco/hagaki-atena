<?php

namespace GSSHagaki;

require(__DIR__ . '/../vendor/autoload.php');

use \Exception;
use setasign\Fpdi\TcpdfFpdi;
use TCPDF_FONTS;

class HagakiAtena
{

	/**
	 * 読み込まれるCSVファイル。
	 *
	 * @var \SplFileObject $file
	 */
	private $file = null;

	/**
	 * はがきインスタンス
	 *
	 * @var Hagaki
	 */
	private $hagaki;

	/**
	 * オプション配列。
	 *
	 * @var array
	 */
	private $options = [];

	/**
	 * GSSHagaki constructor.
	 *
	 * @param string $file
	 * @param array $options
	 */
	public function __construct($file, $options = [])
	{
		$this->hagaki = new Hagaki();
		$this->options($options);
		$datas = $this->readData($file);
		$this->writeData($datas);
		
		$outputFile = realpath($file) . '.pdf';
		$this->output($outputFile);
	}

	/**
	 * オプションを設定する。
	 *
	 * @param $options
	 */
	private function options($options) {
		$this->options = $options;
		// はがきテンプレートを使用する
		if ( isset($options['template']) && (boolean)$options['template']) { // はがきテンプレートを表示する
			$this->hagaki->use_template = true;
		}
		var_dump($options['to_zenkaku']);
		if ( isset($options['to_zenkaku']) && (boolean)$options['to_zenkaku'] ) { // 半角数字を全角にする
			$this->options['to_zenkaku'] = true;
		} else {
			$this->options['to_zenkaku'] = false;
		}
		if ( isset($options['credit']) && (boolean)$options['credit']) { // クレジット表記
			$this->options['credit'] = true;
		} else {
			$this->options['credit'] = false;
		}
		if ( isset($options['debug']) && (boolean)$options['debug']) { // デバッグ
			$this->options['debug'] = true;
		} else {
			$this->options['debug'] = false;
		}
	}

	/**
	 * @param string $file
	 * @return array
	 */
	private function readData($file)
	{

		$this->file = new \SplFileObject($file);
		$this->file->setFlags(\SplFileObject::READ_CSV);
		$header = []; // カラム名が記載された見出し行
		$datas  = [];

		// CSVデータを読み出す
		while ( ! $this->file->eof() && $row = $this->file->fgetcsv()) {
			if ( ! count($header)) { // 見出し行が無かった場合
				$header = $row;
				continue;
			}
			if (count($row) === 1 ||  // 空行だったりした場合
				empty($row[0]) // 最初の列が空の行は省く
			) {
				continue;
			}
			$data	= array_combine($header, $row); // 見出し行を連想配列のキーに設定する
			$datas[] = $data;
		}

		return $datas;
	}

	/**
	 * データを書き込む。
	 *
	 * @param $datas
	 *
	 * @return mixed
	 */
	private function writeData($datas) {
		$this->hagaki->defineHagaki();
		foreach ($datas as $data) {
			if ( empty($data['zipcode']) && $data['address_1'] ) {
				// 郵便番号と住所1がない場合にはスキップする
				continue;
			}
			$this->hagaki->addPage();
			$this->hagaki->zipcode($data['zipcode']);
			$this->hagaki->address(
				$this->to_zenkaku($data['address_1']),
				$this->to_zenkaku($data['address_2'])
			);


			$names = [];
			for($i=0; $i<4; $i++) {
				$names[$i]['first_name'] = $data['first_name_'. ($i+1)];
				$names[$i]['suffix'] = $data['suffix_'. ($i+1)];
			}
			$this->hagaki->names($data['family_name'], $names);

			$this->hagaki->owner_zipcode($data['owner_zipcode']);
			$this->hagaki->owner_address(
				$this->to_zenkaku($data['owner_address_1']),
				$this->to_zenkaku($data['owner_address_2'])
			);
			$this->hagaki->owner_name($data['owner_name']);

			if ( isset($this->options['debug']) && $this->options['debug'] ) {
				$this->hagaki->addVersion();
			}
			if ( isset($this->options['credit']) && $this->options['credit'] ) {
				$this->hagaki->credit();
			}
		}
		return $datas;
	}

	/**
	 * 半角数字を全角にする
	 */
	private function to_zenkaku($str) {
		return str_replace([0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
			['〇', '一', '二', '三', '四', '五', '六', '七', '八', '九'],
			$str
	);
	}

	/**
	 * ブラウザに出力する。
	 *
	 * @link  https://qiita.com/horimislime/items/325848fcf1e3dc6bd53a
	 * 下記のリンクは恐らく古い情報で、'O'は未サポート。 @see method of tcpdf
	 * @link https://stackoverflow.com/questions/31198949/how-to-send-the-file-inline-to-the-browser-using-php-with-tcpdf
	 * @param string $file 書き込むファイル名
	 */
	private function output($file)
	{
		$this->hagaki->Output($file, 'F');
	}
}