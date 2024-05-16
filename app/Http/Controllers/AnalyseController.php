<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Analyse;
use App\Models\XmlProgramme;
use App\Models\PageProgramme;

class AnalyseController extends Controller
{
    public function analyse(Request $request)
    {
        $validator = $this->validateRequest($request);

        if ($validator->fails()) {
            return $this->redirectToFormWithError($validator);
        }

        if($request->type === 'page'){
            $pageProgramme = new PageProgramme();
            $pageProgramme->handle($request->url, md5(microtime()));
            return redirect()->back()->with('success', 'Screenshot saved');
        }
        elseif($request->type === 'xml'){
            $xmlContent = file_get_contents($request->url);
            $xmlProgramme = new XmlProgramme();
            $name = md5(microtime());
            $xmlProgramme->generateSummary($xmlContent, $name);
        }
        return redirect()->back()->with('error', 'Something went wrong. Please try again.');
    }

    public function show()
    {
        return view('form');
    }

    private function validateRequest(Request $request)
    {
        return Validator::make($request->all(), [
            'url' => 'required|url',
        ]);
    }
}
