<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Models\Group;
use Models\Setting;
use Models\Upload;

class InitializationController extends Controller
{
    protected static $init;

    public static function isInitialized()
    {
        $values = self::getInitValues();

        return $values['has_user'] && $values['has_azienda'] && $values['has_settings'];
    }

    public static function getInitValues()
    {
        if (!isset(self::$init)) {
            $database = database();

        $has_azienda = $database->fetchNum("SELECT `an_anagrafiche`.`idanagrafica`
        FROM `an_anagrafiche`
            LEFT JOIN `an_tipianagrafiche_anagrafiche` ON `an_anagrafiche`.`idanagrafica`=`an_tipianagrafiche_anagrafiche`.`idanagrafica`
            LEFT JOIN `an_tipianagrafiche` ON `an_tipianagrafiche`.`idtipoanagrafica`=`an_tipianagrafiche_anagrafiche`.`idtipoanagrafica`
        WHERE `an_tipianagrafiche`.`descrizione` = 'Azienda' AND `an_anagrafiche`.`deleted_at` IS NULL") != 0;
            $has_user = $database->fetchNum('SELECT `id` FROM `zz_users`') != 0;

            $settings = [
                'Regime Fiscale' => true,
                'Tipo Cassa Previdenziale' => false,
                'Conto predefinito fatture di vendita' => true,
                'Conto predefinito fatture di acquisto' => true,
                "Percentuale ritenuta d'acconto" => false,
                "Causale ritenuta d'acconto" => false,
                'Valuta' => true,
            ];

            if (!empty(setting("Percentuale ritenuta d'acconto"))) {
                $settings["Causale ritenuta d'acconto"] = true;
            }

            $has_settings = true;
            foreach ($settings as $setting => $required) {
                if (empty(setting($setting)) && $required) {
                    $has_settings = false;
                    break;
                }
            }

            self::$init = [
                'has_user' => $has_user,
                'has_azienda' => $has_azienda,
                'has_settings' => $has_settings,
                'settings' => $settings,
            ];
        }

        return self::$init;
    }

    public function index(Request $request)
    {
        $database = database();
        $values = self::getInitValues();

        $settings = [];
        foreach ($values['settings'] as $setting => $required) {
            if (empty(setting($setting))) {
                $settings[] = Setting::pool($setting)->input($required);
            }
        }

        // Form dell'anagrafica Azienda
        ob_start();
        $module_id = module('Anagrafiche')->id;
        $idtipoanagrafica = $database->fetchOne("SELECT idtipoanagrafica AS id FROM an_tipianagrafiche WHERE descrizione='Azienda'")['id'];
        $readonly_tipo = true;
        $skip_permissions = true;
        include base_path('legacy').'/modules/anagrafiche/add.php';
        $anagrafica = ob_get_clean();

        $form = str_replace('</form>', '', $anagrafica);

        $args = [
            'has_user' => $values['has_user'],
            'has_azienda' => $values['has_azienda'],
            'has_settings' => $values['has_settings'],
            'settings' => $settings,
            'azienda_form' => $form,
        ];

        return view('config.initialization', $args);
    }

    public function save(Request $request)
    {
        $database = database();
        $values = self::getInitValues();

        $has_user = $values['has_user'];
        $has_azienda = $values['has_azienda'];
        $has_settings = $values['has_settings'];
        $settings = $values['settings'];

        // Azienda predefinita
        if (!$has_azienda) {
            $this->saveAnagrafica();
        }

        // Utente amministratore
        if (!$has_user) {
            // Creazione utente Amministratore
            $gruppo = Group::where('nome', '=', 'Amministratori')->first();

            $username = $request->input('admin_username');
            $password = $request->input('admin_password');
            $email = $request->input('admin_email');

            $user = User::build($gruppo, $username, $email, $password);

            $id_record = $database->selectOne('an_anagrafiche', ['idanagrafica'])['idanagrafica'];
            $user->idanagrafica = isset($id_record) ? $id_record : 0;
            $user->save();
        }

        if (!$has_settings) {
            foreach ($settings as $setting => $required) {
                $setting = Setting::pool($setting);

                $value = $request->input('setting.'.$setting['id']);
                if (!empty($value)) {
                    Setting::pool($setting['nome'])->setValue($value);
                }
            }
        }

        return redirect(route('initialization'));
    }

    protected function saveAnagrafica()
    {
        $dbo = $database = database();

        $this->filter->set('post', 'op', 'add');
        $id_module = module('Anagrafiche')['id'];
        include base_path('legacy').'/modules/anagrafiche/actions.php';

        // Logo stampe
        if (!empty($_FILES) && !empty($_FILES['blob']['name'])) {
            $file = Upload::build($_FILES['blob'], [
                'name' => 'Logo stampe',
                'id_module' => $id_module,
                'id_record' => $id_record,
            ]);

            Setting::pool('Logo stampe')->setValue($file);
        }
    }
}
