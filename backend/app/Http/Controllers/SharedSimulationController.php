<?php

namespace App\Http\Controllers;

use App\Models\Simulation;
use Illuminate\Contracts\View\View;

/**
 * The server rendered half of a shared link.
 *
 * The SPA already renders /risultato/{token} for a human, so this page is not here to show the
 * figures a second time: it is here because a crawler never runs the SPA. A link pasted into a
 * chat, a mail client or a social feed is fetched by a bot that reads the <head> and leaves,
 * and of a client rendered app that head says the same generic thing for every simulation.
 * Rendering server side is what puts this simulation's net figure into the preview card.
 *
 * It reads the stored snapshot and nothing else: no calculator, no tax tables, no recomputation.
 * A link shared today keeps showing today's figures however the rates move afterwards, which is
 * the whole reason `simulations.result` is a snapshot rather than a cache.
 */
class SharedSimulationController extends Controller
{
    public function __invoke(string $token): View
    {
        $simulation = Simulation::with('municipality')
            ->where('token', $token)
            ->firstOrFail();

        return view('shared-simulation', ['simulation' => $simulation]);
    }
}
