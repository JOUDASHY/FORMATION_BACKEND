<?php

namespace App\Http\Controllers;

use App\Models\Billet;
use App\Models\BilletTemp;
use App\Models\Commande;
use App\Models\CommandeTemp;
use App\Models\evenement;
use App\Models\User;
use Barryvdh\DomPDF\PDF;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use phpseclib\Crypt\TripleDES;
use SimpleSoftwareIO\QrCode\Facades\QrCode;


class CommandeController extends Controller
{
    public function mobileSuccess (Request $request) {
        $validator = Validator::make($request->all(), [
            "nom" => 'required',
            "total" => 'required'
        ]);

        if ($validator->fails()) {
            return response([
                "Message" => "There is an empty things dude "
            ]);
        } 

    $idAcheteur = $request->id;
    $email = $request->email;

    // Récupération des billets correspondant à l'ID de l'acheteur
    $billets = BilletTemp::where('userBuy', $idAcheteur)->get();

    // Migration des billets vers la table Billet
    foreach ($billets as $element) {
        Billet::create([
            'user_id' => $element->user_id,
            'title' => $element->title,
            'event_id' => $element->event_id,
            'prix' => $element->prix,
            'token' => $element->token,
            'userBuy' => $element->userBuy,
        ]);
    }

    // Suppression des billets correspondant à l'ID de l'acheteur de la table BilletTemp
    BilletTemp::where('userBuy', $idAcheteur)->delete();

    // Génération des PDFs pour les billets
    $pdfs = [];
    foreach ($billets as $element) {
        $pdfContent = '<h1>' . $element->title . '</h1>';
        $qrCodeContent = $element->token;
        $qrCode = QrCode::size(200)->generate($qrCodeContent);
        $pdfContent .= '<img src="data:image/png;base64,' . base64_encode($qrCode) . '">';

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($pdfContent);
        $pdfs[] = [
            'content' => $pdf->output(),
            'filename' => 'Billets numero' . $element->id . '.pdf'
        ];
    }

    // Envoi des PDFs par email
    // $email = CommandeTemp::where('user_id', $idAcheteur)->first();
    Mail::raw('Veuillez trouver ci-joint vos PDF avec les codes QR.', function ($message) use ($pdfs, $email) {
        $message->to($email)
            ->subject('PDFs avec codes QR');

        foreach ($pdfs as $pdf) {
            $message->attachData($pdf['content'], $pdf['filename'], [
                'mime' => 'application/pdf',
            ]);
        }
    });

    // Suppression des données dans CommandeTemp
    CommandeTemp::where('user_id', $idAcheteur)->delete();

    // Redirection vers la page de succès
    return redirect('http://localhost:3000/success?id_acheteur=' . $idAcheteur);
    }

    public function mobileMoney(Request $request)
    {
        // Enregistrement de la commande et aussi du billets dans  la bd 
        $donnees = $request->data; // Récupérer les données de la requête
        $email = [$request->email];
        foreach ($donnees as $element) {
            //Decrement le nombre de billets disponible
            $event = evenement::find($element['id']);
            $event->update([
                'limitBillets' => $event->limitBillets - $element['quantity'],
                'billetsVendus' => $event->billetsVendus + $element['quantity']
            ]);
            $event->save();
            for ($i = 0; $i < $element['quantity']; $i++) {
                $randomString = bin2hex(random_bytes(16)); // Génère une chaîne hexadécimale aléatoire de 32 caractères
                $token =  uniqid() . $randomString;
                // Stocker les informations dans le tableau temporaire
                BilletTemp::create([
                    'user_id' => $element['user_id'],
                    'title' => $element['titre'],
                    'event_id' => $element['id'],
                    'prix' => $element['prix'],
                    'token' => $token , 
                    'userBuy' => $request->user_id,
                ]);

                //Generation des pdfs pour les billets
                $pdfContent = '<h1>' . $element['titre'] . '</h1>';
                $qrCodeContent = $token;
                $qrCode = QrCode::size(200)->generate($qrCodeContent);
                $pdfContent .= '<img src="data:image/png;base64,' . base64_encode($qrCode) . '">';

                $pdf = app('dompdf.wrapper');
                $pdf->loadHTML($pdfContent);
                $pdfs[] = [
                    'content' => $pdf->output(),
                    'filename' => 'Billets numero' . $i . '.pdf'
                ];
            }

            /// Insérer chaque élément dans la base de données//b n
            CommandeTemp::create([
                'user_id' => $request->user_id,
                'titreEvent' => $element['titre'],
                'event_id' => $element['id'],
                'quantite' => $element['quantity'],
                'montantTotal' => $element['quantity'] * $element['prix'],
                'email' => $request->email,
            ]);
        }

        //atao reference 
        $id = $request->user_id;
        
        // Exemple de données à envoyer
        $total = $request->total; // Montant à payer
        $nom = $request->nom; // Nom du payeur
        $mail = 'mail@mail.com'; // Adresse email du payeur
        $site_url = 'https://decryptage-vanila-2-0.onrender.com'; // URL du site e-commerce
        $ip = $request->ip(); // Adresse IP du client (Laravel peut détecter l'IP)
        $now = new DateTime(); // Date du paiement
        $daty = $now->format('Y-m-d'); // Formattage de date

        // Clés de sécurité
        $public_key = '4c36cff9f00736b959c123ffcabf853aeae44d956b66f35f43'; // Clé publique obtenue de la plateforme AriaryNet
        $private_key = 'c2feffa73e0933a6220ed9296bd7551c77a19d35ec23643016'; // Clé privée obtenue de la plateforme AriaryNet

        // Authentification pour obtenir le token
        $auth_params = [
            'client_id' => '399_4umss3k06x6oc8gcg04gcw4wokkokw080wssg40k040oksk8c4',
            'client_secret' => '2fbaa5olmlxc8o4ssco40oosk840840k40ckk0008k8wggc80w',
            'grant_type' => 'client_credentials'
        ];

        $curl = curl_init();
        $url = 'https://pro.ariarynet.com/oauth/v2/token';

        curl_setopt($curl, CURLOPT_HTTPHEADER, []);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $auth_params);
        curl_setopt($curl, CURLOPT_URL, $url);
        $result = curl_exec($curl);
        curl_close($curl);

        // Obtenir le token
        $json = json_decode($result);
        // dd($json);
        $token = $json->access_token;
        // dd($token);

        // Headers pour les requêtes API
        $headers = ["Authorization:Bearer " . $token];

        // Cryptage TripleDES avec mode CBC et IV
        $des = new TripleDES();
        $des->setKey($public_key);
        $des->setIV(str_repeat("\0", 8)); // IV de 8 octets pour TripleDES

        $id = $request->user_id;
        $email = $request->email;

        // Données à envoyer pour la transaction
        $params_to_send = array(
            "unitemonetaire" => "Ar",
            "adresseip"      => $request->ip(), // Utilisation de l'IP réelle du client
            "date"           => $daty,
            "idpanier"       => uniqid(), // ID de panier unique généré
            "montant"        => $total,
            "nom"            => $email,
            "reference"      => $id,
            "site_url" => $site_url // Référence interne optionnelle
        );
        // Appel de l'API pour obtenir l'ID de paiement
        $curl = curl_init();
        $url = 'https://pro.ariarynet.com/api/paiements';

        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params_to_send); // Envoyer les paramètres en tant que JSON
        curl_setopt($curl, CURLOPT_URL, $url);
        $result = curl_exec($curl);

        // dd($result);

        if ($result === false) {
            dd('Erreur cURL : ' . curl_error($curl));
        }
        curl_close($curl);


        // Décryptage de l'ID de paiement (vérifiez d'abord si $result est correct)
        $des->setKey($private_key);

        $id = $des->decrypt($result);
        // dd($id);
        return response("https://moncompte.ariarynet.com/payer/{$id}");

    }

    public function cancel()
    {
        $message = "Votre paiement a été annulé";
        return redirect('http://localhost:3000/.?message=' . urlencode($message));
    }

    //annuler la commande d'un client en supprimant les donnees dans commandeTemp et billetTemp du client 
    public function annulerCommandeParUser(User $user)
    {
        //Partie commandeTemp
        $commandeTemp = CommandeTemp::where('user_id', $user->id)->get();
        foreach ($commandeTemp as $element) {
            $event = evenement::find($element->event_id);
            $event->update([
                'limitBillets' => $event->limitBillets + $element->quantite,
                'billetsVendus' => $event->billetsVendus - $element->quantite
            ]);
            $event->save();
        }
        CommandeTemp::where('user_id', $user->id)->delete();

        //Partie billetTemp
        BilletTemp::where('userBuy', $user->id)->delete();

        
        $message = "Votre commande a été annulé";
        
        return response($message);
    }

    

    public function redirigerVersPageLocale(Request $request)
{
    // Récupération de l'ID de l'acheteur à partir de la variable de session de Stripe
    $idAcheteur = $request->id;
    $email = $request->email;

    // Récupération des billets correspondant à l'ID de l'acheteur
    $billets = BilletTemp::where('userBuy', $idAcheteur)->get();

    // Migration des billets vers la table Billet
    foreach ($billets as $element) {
        Billet::create([
            'user_id' => $element->user_id,
            'title' => $element->title,
            'event_id' => $element->event_id,
            'prix' => $element->prix,
            'token' => $element->token,
            'userBuy' => $element->userBuy,
        ]);
    }

    // Suppression des billets correspondant à l'ID de l'acheteur de la table BilletTemp
    BilletTemp::where('userBuy', $idAcheteur)->delete();

    // Génération des PDFs pour les billets
    $pdfs = [];
    foreach ($billets as $element) {
        $pdfContent = '<h1>' . $element->title . '</h1>';
        $qrCodeContent = $element->token;
        $qrCode = QrCode::size(200)->generate($qrCodeContent);
        $pdfContent .= '<img src="data:image/png;base64,' . base64_encode($qrCode) . '">';

        $pdf = app('dompdf.wrapper');
        $pdf->loadHTML($pdfContent);
        $pdfs[] = [
            'content' => $pdf->output(),
            'filename' => 'Billets numero' . $element->id . '.pdf'
        ];
    }

    // Envoi des PDFs par email
    // $email = CommandeTemp::where('user_id', $idAcheteur)->first();
    Mail::raw('Veuillez trouver ci-joint vos PDF avec les codes QR.', function ($message) use ($pdfs, $email) {
        $message->to($email)
            ->subject('PDFs avec codes QR');

        foreach ($pdfs as $pdf) {
            $message->attachData($pdf['content'], $pdf['filename'], [
                'mime' => 'application/pdf',
            ]);
        }
    });

    // Suppression des données dans CommandeTemp
    CommandeTemp::where('user_id', $idAcheteur)->delete();

    // Redirection vers la page de succès
    return redirect('http://localhost:3000/success?id_acheteur=' . $idAcheteur);
}

    public function index(Request $request)
    {
        // Enregistrement de la commande et aussi du billets dans  la bd 
        $donnees = $request->data; // Récupérer les données de la requête
        $email = [$request->email];
        foreach ($donnees as $element) {
            //Decrement le nombre de billets disponible
            $event = evenement::find($element['id']);
            $event->update([
                'limitBillets' => $event->limitBillets - $element['quantity'],
                'billetsVendus' => $event->billetsVendus + $element['quantity']
            ]);
            $event->save();
            for ($i = 0; $i < $element['quantity']; $i++) {
                $randomString = bin2hex(random_bytes(16)); // Génère une chaîne hexadécimale aléatoire de 32 caractères
                $token =  uniqid() . $randomString;
                // Stocker les informations dans le tableau temporaire
                BilletTemp::create([
                    'user_id' => $element['user_id'],
                    'title' => $element['titre'],
                    'event_id' => $element['id'],
                    'prix' => $element['prix'],
                    'token' => $token , 
                    'userBuy' => $request->user_id,
                ]);

                //Generation des pdfs pour les billets
                $pdfContent = '<h1>' . $element['titre'] . '</h1>';
                $qrCodeContent = $token;
                $qrCode = QrCode::size(200)->generate($qrCodeContent);
                $pdfContent .= '<img src="data:image/png;base64,' . base64_encode($qrCode) . '">';

                $pdf = app('dompdf.wrapper');
                $pdf->loadHTML($pdfContent);
                $pdfs[] = [
                    'content' => $pdf->output(),
                    'filename' => 'Billets numero' . $i . '.pdf'
                ];
            }

            /// Insérer chaque élément dans la base de données//b n
            CommandeTemp::create([
                'user_id' => $request->user_id,
                'titreEvent' => $element['titre'],
                'event_id' => $element['id'],
                'quantite' => $element['quantity'],
                'montantTotal' => $element['quantity'] * $element['prix'],
                'email' => $request->email,
            ]);
        }

        \Stripe\Stripe::setApiKey(config('stripe.sk'));

        $session = \Stripe\Checkout\Session::create([
            'line_items' => [[
                'price_data' => [
                    'currency' => 'mga',
                    'product_data' => [
                        'name' => 'Achats de billets avec EventPass',
                    ],
                    'unit_amount' => $request->total,
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => route('success', ['id' => $request->user_id , 'email' => $request->email]),
            'cancel_url' => route('cancel'),
        ]);

        return response($session->url);
    }

    //Historique du client connecter
    public function historiqueParUser(User $user)
    {
        $data = $user->commandes()->get();
        return response()->json($data);
    }
    //recuperer le chiffres le revenus total d'un organisateur
    public function revenusParOrganisateur(User $user)
    {
        $data = $user->eventy()->get();
        $revenus = 0;
        foreach ($data as $element) {
            $revenus += $element->billetsVendus * $element->prix;
        }

        return response()->json($revenus);
    }

    public function success()
    {
        return response()->json(['message' => 'Payment successfully done']);
    }

    //somme de tous les montants des commandes
    public function totalCommande()
    {
      $monntanTotal = Commande::sum('montantTotal');
      $total = intval($monntanTotal); // Cast to integer
        return response()->json($total);
    }   
}