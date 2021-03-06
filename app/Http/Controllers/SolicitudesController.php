<?php

namespace App\Http\Controllers;

use App\Solicitude;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class SolicitudesController extends BaseController
{
    //Create and Store a new Solicitude
    public function store(Request $request)
    {
        Log::debug($request->all());
        $messages = [
            'description.required'  =>  'La descripción es requerida',
            'email.required'        =>  'El email es requerido',
            'email.email'           =>  'El email tiene que ser válido',
            'email.not_regex'       =>  'No se permiten correos de Hotmail',
            'phone.required'        =>  'El teléfono es requerido',
            'address.required'      =>  'La dirección es requerida',
            'name.required'         =>  'El nombre es requerido',
        ];

        $validator = Validator::make($request->all(), [
            'description'   =>  'required',
            'email'         =>  ['required','email:rfc,dns','not_regex:/(.*)@hotmail\.[a-zA-Z0-9_]/i'],
            'phone'         =>  'required',
            'address'       =>  'required',
            'name'          =>  'required',
        ], $messages);

        if ($validator->fails())
            return $this->sendError('Accesso Denegado',$validator->errors(), 409);

        $user = User::where('email', $request->email)->get()->first();

        if ($user)
        {
            $user->phone    =   $request->phone;
            $user->address  =   $request->address;
            $user->save();
        }
        else
        {
            $user = new User();
            $user->name     =   $request->name;
            $user->email    =   $request->email;
            $user->phone    =   $request->phone;
            $user->address  =   $request->address;
            $user->save();
        }

        $solicitude = new Solicitude($request->all());
        $solicitude->user_id = $user->id;
        $solicitude->save();

        return $this->sendResponse($solicitude,'Solicitud creada correctamente');

    }

    //Update the Solicitude
    public function update(Request $request, $id)
    {
        $solicitude = Solicitude::find($id);

        if (!$solicitude)
            return $this->sendError('Acceso Denegado','No existe la Solicitud.',403);

        if ($solicitude->status != 'Pendiente')
            return $this->sendError('Acceso Denegado','La solicitud no se encuentra en estado Pendiente.',403);

        $solicitude->title          =   $request->title;
        $solicitude->description    =   $request->description;
        $solicitude->category       =   $request->category;
        $solicitude->save();

        return $this->sendResponse($solicitude,'Solicitud actualizada correctamente');
    }

    //Public the Solicitude
    public function publicSolicitude($id)
    {
        $solicitude = Solicitude::find($id);

        if (!$solicitude)
            return $this->sendError('Acceso Denegado','No existe la Solicitud.',403);

        if ($solicitude->status != 'Pendiente')
            return $this->sendError('Acceso Denegado','La solicitud no se encuentra en estado Pendiente.',403);

        if (!$solicitude->title)
            return $this->sendError('Acceso Denegado','La solicitud no tiene titulo. Edite la Solicitud y pongale titulo.',403);

        if (!$solicitude->category)
            return $this->sendError('Acceso Denegado','La solicitud no tiene categoria. Edite la Solicitud y asignele una categoria.',403);

        $solicitude->status = 'Publicada';
        $solicitude->save();

        return $this->sendResponse($solicitude, 'Solicitud actualizada correctamente');
    }

    //Discard the Solicitude
    public function discardSolicitude($id)
    {
        $solicitude = Solicitude::find($id);

        if (!$solicitude)
            return $this->sendError('Acceso Denegado','No existe la Solicitud.',404);

        if ($solicitude->status == 'Descartada')
            return $this->sendError('Acceso Denegado','La solicitud ya se encuentra en estado Descartada.',403);

        $solicitude->status = 'Descartada';
        $solicitude->save();

        return $this->sendResponse($solicitude, 'Solicitud actualizada correctamente');
    }

    //List the Solicitudes by User
    public function listSolicitudes($email)
    {
        $user = User::where('email', $email)->get()->first();

        if (!$user)
            return $this->sendError('Acceso Denegado','No existe un usuario con ese email.', 404);

        $solicitudes = $user->solicitudes()->paginate(10);

        return $this->sendResponse($solicitudes, 'Listado de Solicitudes');
    }
}
