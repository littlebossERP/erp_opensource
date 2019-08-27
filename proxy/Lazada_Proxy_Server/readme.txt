ApiEntry.php是入口

本来 lazada linio jumia都用同一个Proxy入口

后来lazada 接口改变了返回数据结构，然后分出 ApiEntryV2.php版本
再后来18年5月lazada 换了一套接口，再分出了ApiEntryV3.php版本

所以目前ApiEntryV2.php版本已经废弃，ApiEntry.php依然用于linio和jumia，ApiEntryV3.php用于lazada

