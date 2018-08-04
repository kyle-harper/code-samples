<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Auth;
use Storage;

class LibraryController extends Controller
{
    /**
     * Return a listing of Library items.
     *
     * @param  \Illuminate\Http\Request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $format = strtolower($request->f);
        switch ($format) {
            case 'datatable':

                return ['data' => \App\Library::allowed()->orderBy('updated_at', 'desc')->get()];
            case 'multiselect':
                $items = \App\Library::allowed()->orderBy('file_name')->get();
                foreach ($items as $key => $item) {
                    $items[$key] = $item->only(['id', 'file_name']);
                }

                return $items;
        }

        return \App\Library::allowed()->orderBy('updated_at', 'desc')->get();
    }

    /**
     * Return information about a single Library model to the client.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        $item = \App\Library::find((int)$id);
        if ($item && Auth::user()->can('view', $item)) {

            return $item->prepare($request->time_machine_target);
        }

        return [
            'file_name' => 'New Item',
            'description' => '',
            'file_path' => '',
            'source' => 'file',
        ];
    }

    /**
     * Add the Library model and attachment to storage.
     *
     * @param  Illuminate\Http\Request $request
     * @return Illuminate\Http\Response
     *
     */
    public function store(Request $request)
    {
        $response = [
            'status' => 'danger',
            'message' => 'Oops - something went wrong!',
        ];

        $user = Auth::user();

        // add the attachment to storage if a file was provided, or just pass the URL through if a web resource was provided
        $attachmentPath = null;
        if ($request->source === 'file' && $request->has('attachment')) {
            $attachmentPath = $request->file('attachment')->store('public/library');
            $attachmentPath = preg_replace('/^public/', 'storage', $attachmentPath);
        } elseif ($request->source === 'web' && $request->has('url')) {
            $attachmentPath = $request->url;
        }

        if ($attachmentPath && $user->can('create', \App\Library::class)) {
            $item = \App\Library::create([
                'user_id' => Auth::id(),
                'file_path' => $attachmentPath,
                'file_name' => $request->name,
                'description' => $request->description,
            ]);
            $response['status'] = 'success';
            $response['message'] = 'Item added to Library.';
            $response['id'] = $item->id;
        }

        return $response;
    }

    /**
     * Update the Library model and attachment in storage.
     *
     * @param  Illuminate\Http\Request $request
     * @param  int $id
     * @return Illuminate\Http\Response
     *
     */
    public function update(Request $request, $id)
    {
        $response = [
            'notification' => [
                'status' => 'success',
                'message' => 'Oops - something went wrong!',
            ],
        ];

        $item = \App\Library::find((int)$id);
        $user = Auth::user();

        // verify ACLs before updating
        if ($item && $user->can('update', $item)) {
            // check and see if the file/url was changed
            // add the attachment to storage if a file was provided, or just pass the URL through if a web resource was provided
            $attachmentPath = null;
            if ($request->source === 'file' && $request->has('attachment')) {
                $attachmentPath = $request->file('attachment')->store('public/library');
                $attachmentPath = preg_replace('/^public/', 'storage', $attachmentPath);
            } elseif ($request->source === 'web' && $request->has('url')) {
                $attachmentPath = $request->url;
            }
            $item->update([
                'file_path' => $attachmentPath ?? $item->file_path,
                'file_name' => $request->name,
                'description' => $request->description,
            ]);
            $response['notification']['status'] = 'success';
            $response['notification']['message'] = 'Library item updated.';
            $response['item'] = $item->prepare();
        }

        return $response;
    }

    /**
     * Soft-delete the library item in storage.
     *
     * @param  \Illuminate\Http\Request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $response = [
            'status' => 'danger',
            'message' => 'Oops - something went wrong!',
        ];

        $item = \App\Library::find((int)$id);
        if ($item && Auth::user()->can('delete', $item)) {
            $item->delete();

            $response['status'] = 'success';
            $response['message'] = 'Library item deleted.';
        }

        return $response;
    }
}
