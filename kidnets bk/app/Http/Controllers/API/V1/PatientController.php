<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Patient;

use App\Http\Resources\PatientCollection;
use App\Http\Requests\StorePatientRequest;
use App\Http\Resources\PatientResource;
use App\Http\Resources\PatientDetailResource;

use App\Traits\HasPermissionCheck;
use App\Traits\UserRoleCheck;
use Illuminate\Support\Facades\Log;

class PatientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    use UserRoleCheck;
    use HasPermissionCheck;
    public function index(Request $request)
    {
        $doctorId = $this->checkUserRole(['superadmin', 'access_patient', 'insert_patient', 'update_patient', 'delete_patient', 'detail_patient']);

        $searchQuery = $request->input('searchQuery');
        $patients =   Patient::where('doctor_id', $doctorId)->with('appointments', 'Ordonance')
            ->orderBy('id', 'desc')
            ->paginate($request->get('per_page', 20));

        if (!empty($searchQuery)) {
            // If there's a search query, apply search filters
            $patients = Patient::where('doctor_id', $doctorId)->with('appointments', 'Ordonance')
                ->where(function ($query) use ($searchQuery) {
                    $query->where('nom', 'like', "%{$searchQuery}%")
                        ->orWhere('prenom', 'like', "%{$searchQuery}%");
                    // Add more fields to search if necessary
                })
                ->orderBy('id', 'desc')
                ->paginate($request->get('per_page', 20));
        }

        return new PatientCollection($patients);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePatientRequest $request)
    {
        $doctorId = $this->checkUserRole(['superadmin', 'insert_patient', 'access_patient']);
        try {
            $requestData = $request->validated();
            $requestData['doctor_id'] = $doctorId;
            $data = new PatientResource(Patient::create($requestData));
            return response()->json([
                'message' => 'Patient created successfully',
                'data' => $data
            ], 201);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to create patient',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function patientDetails(string $id)
    {
        $doctorId = $this->checkUserRole(['superadmin', 'detail_patient', 'access_patient']);
        return  new PatientDetailResource(Patient::where('doctor_id', $doctorId)->with('appointments', 'operations', 'operations.operationdetails', 'Xray', 'Ordonance', 'operationsNote')->where('id', $id)->first());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $doctorId = $this->checkUserRole(['superadmin', 'access_patient']);
        return  new PatientResource(Patient::where('doctor_id', $doctorId)->where('id', $id)->first());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StorePatientRequest $request, string $id)
    {
        $doctorId = $this->checkUserRole(['superadmin', 'update_patient', 'access_patient']);
        $patient = Patient::where('doctor_id', $doctorId)->findOrFail($id);
        if (!$patient) {
            return response()->json([
                'message' => 'Patient not found.',
            ], 404);
        }

        // Validate the updated data
        $validatedData = $request->validated();
        Log::Info($validatedData);
        // Update patient details
        $patient->update($validatedData);

        return response()->json([
            'message' => 'Patient updated successfully.',
            'data' =>  new PatientResource($patient),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        $doctorId = $this->checkUserRole(['superadmin', 'delete_patient', 'access_patient']);
        Patient::where('doctor_id', $doctorId)->findorfail($id)->delete();
        return response()->json(['message' => 'patient deleted successfully'], 204);
    }
    public function patientTinyData(string $id)
    {
        $doctorId = $this->checkUserRole();
        $patient =  Patient::where('doctor_id', $doctorId)->where('id', $id)->select('nom', 'prenom')->first();
        return response()->json([
            'message' => 'Patient updated successfully.',
            'data' =>
            $patient,
        ], 200);
    }
}
