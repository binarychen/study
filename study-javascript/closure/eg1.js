var a = 1;

function c() {
  var a = 2;
  return function () {
    console.log(a);
  }
}

c()(); // 2


/**
 * �հ��Ǵ�����봴���ô��������������ݵĽ��
 */
