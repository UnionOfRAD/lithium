<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\tests\integration\test;

use lithium\test\filter\Coverage;
use lithium\test\Group;
use lithium\test\Report;

class FilterTest extends \lithium\test\Integration {

	public function setUp() {
		$this->report = new Report(array(
			'title' => '\lithium\tests\mocks\test\MockFilterTest',
			'group' => new Group(
				array('data' => array('\lithium\tests\mocks\test\MockFilterClassTest'))
			)
		));
	}

	public function testSingleTest() {
		$this->report->filters(array("Coverage" => ""));

		$this->report->run();

		$expected = 40;

		$filter = $this->report->results['filters']['lithium\test\filter\Coverage'];
		$data = $filter['lithium\tests\mocks\test\MockFilterClass'];
		$result = $data['percentage'];

		$this->assertEqual($expected, $result);
	}

	public function testSingleTestWithMultipleFilters() {
		$all = array(
			"Coverage",
			"Complexity",
			"Profiler",
			"Affected"
		);

		$permutations = $this->power_perms($all);

		foreach ($permutations as $filters) {
			$filters = array_flip($filters);
			$filters = array_map(function($v) {
				return "";
			}, $filters);

			$report = new Report(array(
				'title' => '\lithium\tests\mocks\test\MockFilterTest',
				'group' => new Group(
					array('data' => array('\lithium\tests\mocks\test\MockFilterClassTest'))
				)
			));

			$report->filters($filters);

			$report->run();

			if (array_key_exists("Coverage", $filters)) {
				$expected = 40;

				$result = $report->results['filters'];

				$this->assertTrue(isset($result['lithium\test\filter\Coverage']),
					"Filter(s): '" . join(array_keys($filters), ", ") . "'"
					. "returned no Coverage results."
				);
				$percentage = $result['lithium\test\filter\Coverage'];
				$percentage = $percentage['lithium\tests\mocks\test\MockFilterClass'];
				$percentage = $percentage['percentage'];

				$this->assertEqual($expected, $percentage);
			}
		}
	}

	/**
	 * Methods for getting all permutations of each set in the power set of an array of strings
	 * (from the php.net manual on shuffle)
	 */

	private function power_perms($arr) {
	    $power_set = $this->power_set($arr);
	    $result = array();
	    foreach($power_set as $set) {
	        $perms = $this->perms($set);
	        $result = array_merge($result,$perms);
	    }
	    return $result;
	}

	private function power_set($in,$minLength = 1) {
	   $count = count($in);
	   $members = pow(2,$count);
	   $return = array();
	   for ($i = 0; $i < $members; $i++) {
	      $b = sprintf("%0" . $count . "b", $i);
	      $out = array();
	      for ($j = 0; $j < $count; $j++) {
	         if ($b{$j} == '1') $out[] = $in[$j];
	      }
	      if (count($out) >= $minLength) {
	         $return[] = $out;
	      }
	   }

	   //usort($return,"cmp");  //can sort here by length
	   return $return;
	}

	private function factorial($int){
	   if($int < 2) {
	       return 1;
	   }

	   for($f = 2; $int - 1 > 1; $f *= $int--){}

	   return $f;
	}

	private function perm($arr, $nth = null) {

	    if ($nth === null) {
	        return $this->perms($arr);
	    }

	    $result = array();
	    $length = count($arr);

	    while ($length--) {
	        $f = $this->factorial($length);
	        $p = floor($nth / $f);
	        $result[] = $arr[$p];
	        $this->array_delete_by_key($arr, $p);
	        $nth -= $p * $f;
	    }

	    $result = array_merge($result,$arr);
	    return $result;
	}

	private function perms($arr) {
	    $p = array();
	    for ($i = 0; $i < $this->factorial(count($arr)); $i++) {
	        $p[] = $this->perm($arr, $i);
	    }
	    return $p;
	}

	private function array_delete_by_key(&$array, $delete_key, $use_old_keys = FALSE) {

	    unset($array[$delete_key]);

	    if(!$use_old_keys) {
	        $array = array_values($array);
	    }

	    return TRUE;
	}
}

?>