namespace {:namespace};

use {:use};

class {:class} extends \lithium\action\Controller {

	public function index() {
		${:plural} = {:model}::all();
		return compact('{:plural}');
	}

	public function view() {
		${:singular} = {:model}::first($this->request->id);
		return compact('{:singular}');
	}

	public function add() {
		${:singular} = {:model}::create();

		if (($this->request->data) && ${:singular}->save($this->request->data)) {
			$this->redirect(array('{:name}::view', 'args' => array(${:singular}->id)));
		}
		return compact('{:singular}');
	}

	public function edit() {
		${:singular} = {:model}::find($this->request->id);

		if (!${:singular}) {
			$this->redirect('{:name}::index');
		}
		if (($this->request->data) && ${:singular}->save($this->request->data)) {
			$this->redirect(array('{:name}::view', 'args' => array(${:singular}->id)));
		}
		return compact('{:singular}');
	}
}