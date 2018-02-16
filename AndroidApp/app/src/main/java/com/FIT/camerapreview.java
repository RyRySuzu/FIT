package com.FIT;

import android.app.Activity;
import android.content.Context;
import android.graphics.Bitmap;
import android.hardware.Camera;
import android.hardware.Camera.Size;
import android.view.Surface;
import android.view.SurfaceHolder;
import android.view.SurfaceView;
import java.io.IOException;
import java.util.List;
import android.hardware.Camera.AutoFocusCallback;

/**
 * カメラ画面を表示するためのビュー。
 *
 * @author dai
 *
 */
public class camerapreview extends SurfaceView implements SurfaceHolder.Callback{
    SurfaceHolder mHolder;
    Camera mCamera;
    final Activity mActivity;
    static Bitmap dish,face;
    // カメラの切り替えに必要
    int numberOfCameras,defaultCameraId;

    camerapreview(Activity activity) {
        super(activity);
        mActivity = activity;
        // Install a SurfaceHolder.Callback so we get notified when the
        // underlying surface is created and destroyed.
        mHolder = getHolder();
        mHolder.addCallback(this);
        mHolder.setType(SurfaceHolder.SURFACE_TYPE_PUSH_BUFFERS);
    }

    public void surfaceCreated(SurfaceHolder holder) {
        // The Surface has been created, acquire the camera and tell it where
        // to draw.
        mCamera = Camera.open();
        // パラメータ取得
        Camera.Parameters params = mCamera.getParameters();
        // サポートサイズ取得,Whiteバランス
        List< Size > sizeList = params.getSupportedPictureSizes();
        List< String > whiteList = params.getSupportedWhiteBalance();
        // サイズ：640x480に設定
        params.setPictureSize(640, 480);
        // ホワイトバランス：昼光に設定
        params.setWhiteBalance(Camera.Parameters.WHITE_BALANCE_FLUORESCENT);
        // パラメータ設定
        mCamera.setParameters(params);

        // 利用可能なカメラの個数を取得
        numberOfCameras = Camera.getNumberOfCameras();

        try {
            mCamera.setPreviewDisplay(holder);
        }
        catch (IOException exception) {
            mCamera.release();
            mCamera = null;
            // TODO: add more exception handling logic here
        }
    }

    public void surfaceDestroyed(SurfaceHolder holder) {
        // Surface will be destroyed when we return, so stop the preview.
        // Because the CameraDevice object is not a shared resource, it's very
        // important to release it when the activity is paused.
        mCamera.stopPreview();
        mCamera.release();
        mCamera = null;
    }


    private Size getOptimalPreviewSize(List<Size> sizes, int w, int h) {
        final float ASPECT_TOLERANCE = 0.05f;
        if(w < h) { // sizes are always landscape, as far as I know
            int temp = w;
            w = h;
            h = temp;
        }
        float targetRatio = (float) w / h;
        if (sizes == null) return null;

        Size optimalSize = null;
        float minDiff = Float.MAX_VALUE;

        int targetHeight = h;

        // Try to find an size match aspect ratio and size
        for (Size size : sizes) {
            float ratio = (float) size.width / size.height;
            if (Math.abs(ratio - targetRatio) > ASPECT_TOLERANCE) continue;
            if (Math.abs(size.height - targetHeight) < minDiff) {
                optimalSize = size;
                minDiff = Math.abs(size.height - targetHeight);
            }
        }

        // Cannot find the one match the aspect ratio, ignore the requirement
        if (optimalSize == null) {
            minDiff = Float.MAX_VALUE;
            for (Size size : sizes) {
                if (Math.abs(size.height - targetHeight) < minDiff) {
                    optimalSize = size;
                    minDiff = Math.abs(size.height - targetHeight);
                }
            }
        }
        return optimalSize;
    }

    public void surfaceChanged(SurfaceHolder holder, int format, int w, int h) {
        // Now that the size is known, set up the camera parameters and begin
        // the preview.
        // You need to stop preview if previously started.
        // Otherwise Camera.setDisplayOrientation() throws a exception.
        mCamera.stopPreview();

        Camera.Parameters parameters = mCamera.getParameters();

        List<Size> sizes = parameters.getSupportedPreviewSizes();
        Size optimalSize = getOptimalPreviewSize(sizes, w, h);
        parameters.setPreviewSize(optimalSize.width, optimalSize.height);
        sizes = parameters.getSupportedPictureSizes();
        optimalSize = getOptimalPreviewSize(sizes, w, h);

        // 横にした時のサイズ設定
        if(optimalSize.width>=640){
            optimalSize.width=640;
        }
        if(optimalSize.height>=480){
            optimalSize.height=480;
        }
        parameters.setPictureSize(optimalSize.width, optimalSize.height);

        setCameraDisplayOrientation(mActivity, 0, mCamera);

        mCamera.setParameters(parameters);
        mCamera.startPreview();
    }

    public static void setCameraDisplayOrientation(Activity activity, int cameraId,
                                                   android.hardware.Camera camera) {
        camera.setDisplayOrientation(getCameraDisplayOrientation(activity));
    }
    public static int getCameraDisplayOrientation(Activity activity) {
        int rotation = activity.getWindowManager().getDefaultDisplay().getRotation();
        int degrees = 0;
        switch (rotation) {
            case Surface.ROTATION_0:
                degrees = 0;
                break;
            case Surface.ROTATION_90:
                degrees = 90;
                break;
            case Surface.ROTATION_180:
                degrees = 180;
                break;
            case Surface.ROTATION_270:
                degrees = 270;
                break;
        }
        return (90 + 360 - degrees) % 360;
        // 画面の向きに変化を打ち消す方向にカメラの向きを設定する必要があるため、
        // Display#getRotation()で得られた数値をマイナスにする。
        // Androidは縦向き基本だがカメラは横向き基本なため、90度を追加する。
        // 戻り値が正の一定値になるように、360を足して360で余りを取る。
    }
    // カメラの切り替え
    public void switchCamera() {
        mCamera.stopPreview();
        mCamera.release();

        // CameraInfoからバックフロントカメラのidを取得
        Camera.CameraInfo cameraInfo = new Camera.CameraInfo();
        for (int i = 0; i < numberOfCameras; i++) {
            Camera.getCameraInfo(i, cameraInfo);
            if (cameraInfo.facing == Camera.CameraInfo.CAMERA_FACING_BACK) {
                // iをバックカメラに設定
                defaultCameraId = i;
            }
        }

        if(cameraInfo.facing != Camera.CameraInfo.CAMERA_FACING_BACK){
            cameraInfo.facing = Camera.CameraInfo.CAMERA_FACING_FRONT;
        }else {
            cameraInfo.facing = Camera.CameraInfo.CAMERA_FACING_BACK;
        }

        mCamera = Camera.open(cameraInfo.facing);
        // プレビューを縦向きにしたいので回転
        mCamera.setDisplayOrientation(90);

        try {
            mCamera.setPreviewDisplay(mHolder);
        } catch (IOException e) {
            // TODO Auto-generated catch block
            if(mCamera != null) {
                mCamera.release();
                mCamera = null;
            }
        }
        mCamera.startPreview();
    }

}
