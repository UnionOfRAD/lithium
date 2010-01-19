namespace {:namespace};

use {:use};

class {:class} extends \lithium\action\Controller {

	public function index() {
		${:plural} = {:model}::all();
		return compact('{:plural}');
	}

	public function view($id = null) {
		${:singular} = {:model}::find($id);
		return compact('{:singular}');
	}

	public function add() {
		if (!empty($this->request->data)) {
			${:singular} = {:model}::create($this->request->data);
			if (${:singular}->save()) {
				$this->redirect(array(
					'controller' => '{:plural}', 'action' => 'view',
					'args' => array(${:singular}->id)
				));
			}
		}
		if (empty(${:singular})) {
			${:singular} = {:model}::create();
		}
		return compact('{:singular}');
	}

	public function edit($id = null) {
		${:singular} = {:model}::find($id);
		if (empty(${:singular})) {
			$this->redirect(array('controller' => '{:plural}', 'action' => 'index'));
		}
		if (!empty($this->request->data)) {
			if (${:singular}->save($this->request->data)) {
				$this->redirect(array(
					'controller' => '{:plural}', 'action' => 'view',
					'args' => array(${:singular}->id)
				));
			}
		}
		return compact('{:singular}');
	}
}