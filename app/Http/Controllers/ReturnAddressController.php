<?php

namespace App\Http\Controllers;

use App\Models\ReturnAddress;
use App\Services\NowPaymentsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use MoneroIntegrations\MoneroPhp\Cryptonote;

class ReturnAddressController extends Controller
{
    /**
     * The cryptonote instance for Monero address validation.
     */
    private $cryptonote;

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        try {
            $this->cryptonote = new Cryptonote();
        } catch (\Exception $e) {
            Log::error('Failed to initialize Monero cryptonote: ' . $e->getMessage());
        }
    }

    /**
     * Display a listing of the resource and the form to add a new address.
     */
    public function index(NowPaymentsService $nowPaymentsService)
    {
        $returnAddresses = Auth::user()->returnAddresses()->orderBy('currency')->orderBy('created_at', 'desc')->get();
        $supportedCurrencies = $nowPaymentsService->getSupportedCurrencies();
        
        return view('return-addresses', compact('returnAddresses', 'supportedCurrencies'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, NowPaymentsService $nowPaymentsService)
    {
        $supportedCurrencies = $nowPaymentsService->getSupportedCurrencies();
        $currencyCodes = array_keys($supportedCurrencies);

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'currency' => ['required', 'string', Rule::in($currencyCodes)],
            'address' => [
                'required',
                'string',
                'min:20',
                'max:200',
                Rule::unique('return_addresses')->where(function ($query) use ($request) {
                    return $query->where('user_id', Auth::id())
                                 ->where('currency', $request->currency);
                }),
            ],
        ], [
            'currency.required' => 'Please select a cryptocurrency.',
            'currency.in' => 'Invalid cryptocurrency selected.',
            'address.required' => 'Please enter a wallet address.',
            'address.min' => 'The wallet address is too short.',
            'address.max' => 'The wallet address is too long.',
            'address.unique' => 'You have already added this address for this currency.',
        ]);
        
        if ($validator->fails()) {
            return redirect()->route('return-addresses.index')
                ->with('error', $validator->errors()->first())
                ->withInput();
        }

        $currency = strtolower($request->currency);
        $address = trim($request->address);

        // Validate address based on currency type
        $isValid = $this->validateCryptoAddress($address, $currency);

        if (!$isValid) {
            $currencyName = $supportedCurrencies[$currency]['name'] ?? strtoupper($currency);
            return redirect()->route('return-addresses.index')
                ->with('error', "Invalid {$currencyName} address. Please check and try again.")
                ->withInput();
        }

        $user = Auth::user();

        // Check if the user has reached the limit of 16 return addresses total
        if ($user->returnAddresses()->count() >= 16) {
            return redirect()->route('return-addresses.index')
                ->with('error', 'You can add a maximum of 16 payout addresses.');
        }

        // Check if user has reached limit of 4 addresses per currency
        if ($user->returnAddresses()->where('currency', $currency)->count() >= 4) {
            $currencyName = $supportedCurrencies[$currency]['name'] ?? strtoupper($currency);
            return redirect()->route('return-addresses.index')
                ->with('error', "You can add a maximum of 4 {$currencyName} addresses.");
        }

        ReturnAddress::create([
            'user_id' => $user->id,
            'currency' => $currency,
            'address' => $address,
        ]);

        $currencyName = $supportedCurrencies[$currency]['name'] ?? strtoupper($currency);
        return redirect()->route('return-addresses.index')
            ->with('success', "{$currencyName} payout address successfully added.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ReturnAddress $returnAddress)
    {
        if ($returnAddress->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $currencyName = $returnAddress->currency_name;
        $returnAddress->delete();

        return redirect()->route('return-addresses.index')
            ->with('success', "{$currencyName} payout address successfully deleted.");
    }

    /**
     * Validate cryptocurrency address based on currency type.
     */
    private function validateCryptoAddress(string $address, string $currency): bool
    {
        try {
            switch ($currency) {
                case 'xmr':
                    return $this->validateMoneroAddress($address);
                case 'btc':
                    return $this->validateBitcoinAddress($address);
                case 'ltc':
                    return $this->validateLitecoinAddress($address);
                case 'eth':
                    return $this->validateEthereumAddress($address);
                case 'usdttrc20':
                    return $this->validateTronAddress($address);
                default:
                    // For unknown currencies, do basic length validation
                    return strlen($address) >= 20 && strlen($address) <= 200;
            }
        } catch (\Exception $e) {
            Log::error("Address validation error for {$currency}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate Monero address using cryptonote library.
     */
    private function validateMoneroAddress(string $address): bool
    {
        if (!$this->cryptonote) {
            return false;
        }

        try {
            $isValid = $this->cryptonote->verify_checksum($address);
            if ($isValid) {
                $this->cryptonote->decode_address($address);
                return true;
            }
        } catch (\Exception $e) {
            Log::debug('Monero address validation failed: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Validate Bitcoin address (basic format check).
     */
    private function validateBitcoinAddress(string $address): bool
    {
        // Bitcoin addresses: Legacy (1...), SegWit (3...), Native SegWit (bc1...)
        if (preg_match('/^[13][a-km-zA-HJ-NP-Z1-9]{25,34}$/', $address)) {
            return true; // Legacy or SegWit
        }
        if (preg_match('/^bc1[a-z0-9]{39,59}$/i', $address)) {
            return true; // Bech32
        }
        return false;
    }

    /**
     * Validate Litecoin address (basic format check).
     */
    private function validateLitecoinAddress(string $address): bool
    {
        // Litecoin addresses: Legacy (L..., M...), Native SegWit (ltc1...)
        if (preg_match('/^[LM3][a-km-zA-HJ-NP-Z1-9]{25,34}$/', $address)) {
            return true;
        }
        if (preg_match('/^ltc1[a-z0-9]{39,59}$/i', $address)) {
            return true;
        }
        return false;
    }

    /**
     * Validate Ethereum address (basic format check).
     */
    private function validateEthereumAddress(string $address): bool
    {
        // Ethereum addresses: 0x followed by 40 hex characters
        return (bool) preg_match('/^0x[a-fA-F0-9]{40}$/', $address);
    }

    /**
     * Validate Tron address for USDT TRC20 (basic format check).
     */
    private function validateTronAddress(string $address): bool
    {
        // Tron addresses start with T and are 34 characters
        return (bool) preg_match('/^T[a-zA-Z0-9]{33}$/', $address);
    }
}
