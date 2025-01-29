<?php 
 namespace App\Http\Controllers;

 use Illuminate\Http\Request;
 use Stripe\Stripe;
 use Stripe\PaymentIntent;
 
 class StripeController extends Controller
 {
    public function processPayment(Request $request)
    {
        Stripe::setApiKey(env('STRIPE_SECRET'));
    
        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount,
                'currency' => 'mga',
                'payment_method' => $request->payment_method,
                'confirm' => true,
                'automatic_payment_methods' => [
                    'enabled' => true,
                    'allow_redirects' => 'never',  // Empêche les méthodes nécessitant des redirections
                ],
            ]);
    
            return response()->json([
                'success' => true,
                'paymentIntent' => $paymentIntent,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()]);
        }
    }
    
 }
 