package com.FIT;

import android.app.Activity;
import android.app.AlertDialog;
import android.app.Dialog;
import android.app.DialogFragment;
import android.app.FragmentManager;
import android.content.ContentResolver;
import android.content.ContentValues;
import android.content.Context;
import android.content.DialogInterface;
import android.content.SharedPreferences;
import android.content.pm.PackageManager;
import android.graphics.Bitmap;
import android.graphics.BitmapFactory;
import android.graphics.Canvas;
import android.graphics.Matrix;
import android.hardware.Camera;
import android.os.Bundle;
import android.os.Environment;
import android.provider.MediaStore;
import android.util.Log;
import android.view.View;
import android.view.ViewGroup;
import android.widget.Button;
import android.widget.ImageView;
import android.widget.TextView;
import android.widget.Toast;
import android.app.Activity;
import android.content.Intent;
import android.graphics.Bitmap;
import android.net.Uri;
import android.os.Bundle;
import android.provider.MediaStore;
import android.util.Base64;
import android.util.Log;
import android.view.View;
import android.view.View.OnClickListener;
import android.widget.Button;
import android.widget.Toast;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.io.BufferedInputStream;
import java.io.BufferedReader;
import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileNotFoundException;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStreamReader;
import java.text.SimpleDateFormat;
import java.util.Calendar;
import java.util.Date;

public class MainActivity  extends Activity {
    // プリファレンス
    private SharedPreferences preference;
    private SharedPreferences.Editor editor;
    private camerapreview mCameraPreviewView;
    private ViewGroup mRootViewGroup;

    Button textView;
    Button button1;
    Button ship_button_blue;

    final Context context_get = this;
    static Bitmap rotatedBitmap;
    static Bitmap rotatedBitmap_before;
    static Bitmap rotatedBitmap2;
    String date_check = getPicFileName();

    // バイトに変換して転送
    Bitmap bitmap_dish;
    Bitmap bitmap_face;
    static byte[] bitmap_shop_send;
    static byte[] bitmap_dish_send;
    static byte[] bitmap_face_send;
    static String image_shop ;
    String image_dish ;
    String image_face ;
    String name_dish = date_check + "dish.png";
    String name_face = date_check + "face.png";

    // 店、食、顔
    int status = 0;

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);
        // プリファレンスの準備
        preference = getSharedPreferences("Preference Name", MODE_PRIVATE);
        editor = preference.edit();

        if (preference.getBoolean("Launched", false) == false) {
            // プリファレンスの書き変え
            editor.putBoolean("Launched", true);
            editor.commit();
        } else {
            // カメラ,xml設定
            mCameraPreviewView = new camerapreview(this);
            mRootViewGroup = (ViewGroup) findViewById(R.id.root_layout);
            mRootViewGroup.addView(mCameraPreviewView);
            button1 = (Button) findViewById(R.id.button);
            ship_button_blue = (Button) findViewById(R.id.button);
            textView = (Button) findViewById(R.id.textView);
            // 画面をタップしたら撮影する
            button1.setOnClickListener(new View.OnClickListener() {
                @Override
                public void onClick(View v) {
                    if(status<=1) {
                        if (mCameraAvailable) { // 写真の生成中は撮影しない
                            mCameraPreviewView.mCamera.takePicture(null, null, null,
                                    kPictureCallback);
                            mCameraAvailable = false;
                        }
                    }else if(status==2){
                        if (status >= 2) {
                            new AlertDialog.Builder(MainActivity.this)
                                    .setTitle("投稿する?")
                                    .setPositiveButton("Yes",
                                            new DialogInterface.OnClickListener() {
                                                @Override
                                                public void onClick(DialogInterface dialog, int which) {
                                                    // ポスト処理を書く
                                                    exec_post();
                                                    status = 0;
                                                    image_dish=null;
                                                    image_face=null;
                                                    // 画面遷移
                                                    Intent intent = new Intent(MainActivity.this,result.class); // 画面指定
                                                    startActivity(intent);
                                                    //finish();
                                                }
                                            })
                                    .setNegativeButton("No",
                                            new DialogInterface.OnClickListener() {
                                                @Override
                                                public void onClick(DialogInterface dialog, int which) {

                                                }
                                            })
                                    .show();
                        } else {
                            new AlertDialog.Builder(MainActivity.this)
                                    .setTitle("枚数が足りません")
                                    .setMessage("もう一度やり直してください。")
                                    .setPositiveButton("OK", null)
                                    .show();
                        }
                    }
                }
            });

            textView.setOnClickListener(new View.OnClickListener() {
                @Override
                public void onClick(View v) {

                }
            });

            BufferedReader in = null;

        }
    }
    /**
     * カメラが現在使用可能かどうか
     */
    private boolean mCameraAvailable = true;

    /**
     * カメラで撮った写真を正しい向きにして返す。
     * 返される画像は変更可能となる。
     *
     * @param cameraBitmap カメラで撮った正しい向きでないBitmap
     * @param degrees      この写真を取ったときの画面の向き。0、90、180、270のいずれか。
     * @return 正しい向きのミュータブルなBitmap
     */

    // 回転用
    private static Bitmap getMutableRotatedCameraBitmap(Bitmap cameraBitmap, int degrees) {
        int width, height;
        // 画面が横向きなら
        if (degrees % 180 == 0) {
            width = cameraBitmap.getWidth();
            height = cameraBitmap.getHeight();
        } else {
            width = cameraBitmap.getHeight();
            height = cameraBitmap.getWidth();
        }
        Bitmap bitmap = Bitmap.createBitmap(width, height, Bitmap.Config.ARGB_8888);
        Canvas canvas = new Canvas(bitmap);
        canvas.rotate(degrees, width / 2, height / 2);

        int offset = (degrees % 180 == 0) ? 0 : (degrees == 90)
                ? (width - height) / 2
                : (height - width) / 2;

        canvas.translate(offset, -offset);
        canvas.drawBitmap(cameraBitmap, 0, 0, null);
        cameraBitmap.recycle();
        return bitmap;
    }

    /**
     * カメラで撮った写真を正しい向きにして返す。
     * 返される画像は変更不可能となる。
     *
     * @param cameraBitmap カメラで撮った正しい向きでないBitmap
     * @param degrees      この写真を取ったときの画面の向き。0、90、180、270のいずれか。
     * @return 正しい向きのイミュータブルなBitmap
     */

    @SuppressWarnings("unused")
    private static Bitmap getImmutableRotatedCameraBitmap(Bitmap cameraBitmap, int degrees) {
        Matrix m = new Matrix();
        m.postRotate(degrees);
        return Bitmap.createBitmap(cameraBitmap, 0, 0, cameraBitmap.getWidth(),
                cameraBitmap.getHeight(), m, false);
    }

    protected String getPicFileName() {
        Calendar c = Calendar.getInstance();
        String s = c.get(Calendar.YEAR)
                + "" + (c.get(Calendar.MONTH) + 1)
                + "" + c.get(Calendar.DAY_OF_MONTH)
                + "" + c.get(Calendar.HOUR_OF_DAY);
        return s;
    }

    /**
     * 写真を撮ったときに呼ばれるコールバック関数
     */
    public Camera.PictureCallback kPictureCallback = new Camera.PictureCallback() {
        @Override
        public void onPictureTaken(byte[] data, Camera camera) {

            Bitmap bitmap = BitmapFactory.decodeByteArray(data, 0, data.length);
            int degrees = camerapreview
                    .getCameraDisplayOrientation(MainActivity.this);
            Matrix matrix = new Matrix();

            // 回転された画像を得る。
            if(status==0){
                rotatedBitmap/*_before*/ = getMutableRotatedCameraBitmap(bitmap, degrees);
            }else {
                rotatedBitmap2 = getMutableRotatedCameraBitmap(bitmap, degrees);
                // 上下左右反転,  デモの時の変更用
                matrix.preScale(-1, -1);
                rotatedBitmap = rotatedBitmap2;
            }
            try {
                saveBitmap(rotatedBitmap);
            } catch (Exception e) {
                e.printStackTrace();
            }

            try {
                saveBitmap(rotatedBitmap);
            } catch (Exception e) {
                e.printStackTrace();
            }

            if (status == 0) {
                ship_button_blue.setBackgroundResource(R.drawable.underbar_2);
            } else if(status==1){
                ship_button_blue.setBackgroundResource(R.drawable.underbar4);
            }
            mCameraPreviewView.mCamera.startPreview();
            mCameraAvailable = true;
            // 状態更新
            status++;
        }
    };

    // 内部ストレージに保存する
    public void saveBitmap(Bitmap saveImage) throws IOException {
        String AttachName_save;
        String fileName_save;

        // セーブするディレクトリ作り
        final String SAVE_DIR = "/" + date_check + "_fit/";
        File file_save = new File(Environment.getExternalStorageDirectory().getPath() + SAVE_DIR);
        try {
            if (!file_save.exists()) {
                file_save.mkdir();
            }
        } catch (SecurityException e) {
            e.printStackTrace();
            throw e;
        }
        // ファイルの名前を決定する
        if (status == 0) {
            fileName_save = date_check + "_dish.jpg";
            bitmap_dish=saveImage;
        } else if (status == 1) {
            fileName_save = date_check + "_face.jpg";
            bitmap_face=saveImage;
        } else {
            fileName_save = date_check + ".jpg";
        }

        // パスを決定する
        AttachName_save = file_save.getAbsolutePath() + "/" + fileName_save;

        try {
            FileOutputStream out = new FileOutputStream(AttachName_save);
            saveImage.compress(Bitmap.CompressFormat.JPEG, 100, out);
            out.flush();
            out.close();
        } catch (IOException e) {
            e.printStackTrace();
            throw e;
        }
        // save index
        ContentValues values = new ContentValues();
        ContentResolver contentResolver = getContentResolver();
        values.put(MediaStore.Images.Media.MIME_TYPE, "image/jpeg");
        values.put(MediaStore.Images.Media.TITLE, fileName_save);
        values.put("_data", AttachName_save);
        contentResolver.insert(MediaStore.Images.Media.EXTERNAL_CONTENT_URI, values);

    }
    //public Bitmap passbit(){
    //    return bitmap_shop;
    //}

    // 通信部分の
    @Override
    public void onRequestPermissionsResult(int requestCode, String[] permissions, int[] grantResults) {
        if (requestCode == 1000) {
            // 使用が許可された
            if (grantResults[0] == PackageManager.PERMISSION_GRANTED) {
                return;

            } else {
                // それでも拒否された時の対応
                Toast toast = Toast.makeText(this, "これ以上なにもできません", Toast.LENGTH_SHORT);
                toast.show();
            }
        }
    }

    // POST通信を実行（AsyncTaskによる非同期処理を使うバージョン）
    private void exec_post() {

        // 非同期タスクを定義
        // HttpPostTaskのjavaプログラムの中身をいじる
        HttpPostTask task = new HttpPostTask(
                this,
                // 通信用URL
                "https://xx/insert_user_data.php",

                // タスク完了時に呼ばれるUIのハンドラ
                new HttpPostHandler(){
                    // 受信する
                    @Override
                    public void onPostCompleted(String response) {
                        // レスポンス取得
                        try {
                            JSONArray jsonArray = new JSONArray(response);
                            for (int i = 0; i <jsonArray.length(); i++) {
                                JSONObject jsonObject = jsonArray.getJSONObject(i);
                                Log.d("JSONSampleActivity", jsonObject.getString("id"));
                            }
                        } catch (JSONException e) {
                            // TODO 自動生成された catch ブロック
                            e.printStackTrace();
                        }

                    }

                    @Override
                    public void onPostFailed(String response) {
                        Toast.makeText(
                                getApplicationContext(),
                                "エラーが発生しました。",
                                Toast.LENGTH_LONG
                        ).show();
                    }
                }
        );
        // bitmap形式を変換して転送
        ByteArrayOutputStream baos = new ByteArrayOutputStream();
        bitmap_dish.compress(Bitmap.CompressFormat.PNG, 100, baos);
        bitmap_dish_send= baos.toByteArray();
        image_dish = Base64.encodeToString(bitmap_dish_send, Base64.DEFAULT);

        // bitmap形式を変換して転送
        ByteArrayOutputStream baos_2 = new ByteArrayOutputStream();
        bitmap_face.compress(Bitmap.CompressFormat.PNG, 100, baos_2);
        bitmap_face_send= baos_2.toByteArray();
        image_face = Base64.encodeToString(bitmap_face_send, Base64.DEFAULT);

        // パラメータを追加することで、データを送れる
        task.addPostParam( "dish_name",name_dish);
        Log.d("posttest", image_dish);
        Log.d("posttest1", image_face);
        task.addPostParam( "face_name",name_face);
        task.addPostParam( "dish_data",image_dish);
        task.addPostParam( "face_data",image_face);

        // タスクを開始
        task.execute();

    }
}
